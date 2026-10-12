<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Routes about the connected user. They have a priority, otherwise API Platform's /api/v3/users/{id} routes would match "me".
 */
#[Route(path: '/api/v3/users/me', format: 'json')]
#[IsGranted('ROLE_USER')]
final class MeApiController extends AbstractController
{
    public function __construct(private readonly NormalizerInterface $normalizer)
    {
    }

    #[Route(path: '', methods: ['GET'], priority: 1)]
    public function me(): JsonResponse
    {
        return $this->json($this->normalizeUser($this->getUser()));
    }

    #[Route(path: '', methods: ['PATCH'], priority: 1)]
    public function update(Request $request, ValidatorInterface $validator, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('UPDATE', $user);
        $payload = $request->toArray();

        foreach (['prenom' => 'setPrenom', 'nom' => 'setNom'] as $field => $setter) {
            if (array_key_exists($field, $payload)) {
                $user->$setter($payload[$field]);
            }
        }
        if (array_key_exists('telephone', $payload)) {
            $user->setTelephone($payload['telephone'] ?: null)->formatPhone();
        }
        if (array_key_exists('email', $payload)) {
            $email = $payload['email'] ?: null;
            if ($user->isMembre() && $user->getEmail() && $email !== $user->getEmail()) {
                throw new BadRequestHttpException('A professional can not change their email');
            }
            $user->setEmail($email);
        }

        $violations = $validator->validate($user, null, ['beneficiaire', 'membre', 'phone']);

        $beneficiary = $user->isBeneficiaire() ? $user->getSubjectBeneficiaire() : null;
        if ($beneficiary) {
            if (array_key_exists('birth_date', $payload)) {
                $birthDate = \DateTime::createFromFormat('!Y-m-d', (string) $payload['birth_date']);
                if (!$birthDate) {
                    throw new BadRequestHttpException('"birth_date" must be formatted as Y-m-d');
                }
                $beneficiary->setDateNaissance($birthDate);
            }
            if (array_key_exists('question_secrete', $payload)) {
                $beneficiary->setQuestionSecrete($payload['question_secrete']);
            }
            if (array_key_exists('reponse_secrete', $payload)) {
                $beneficiary->setReponseSecrete($payload['reponse_secrete']);
            }
            $violations->addAll($validator->validate($beneficiary, null, ['beneficiaire', 'beneficiaireQuestionSecrete']));
        }

        if (count($violations) > 0) {
            // Discard the invalid changes, so that no later flush can save them
            $em->refresh($user);
            if ($beneficiary) {
                $em->refresh($beneficiary);
            }

            return $this->json($this->formatViolations($violations), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $em->flush();

        return $this->json($this->normalizeUser($user));
    }

    /** @return array<string, mixed> */
    private function normalizeUser(User $user): array
    {
        $data = [
            ...$this->normalizer->normalize($user, 'json', ['groups' => ['v3:user:read']]),
            'subject_id' => $user->getSubject()?->getId(),
        ];

        $beneficiary = $user->isBeneficiaire() ? $user->getSubjectBeneficiaire() : null;
        if ($beneficiary) {
            $data['beneficiary'] = $this->normalizer->normalize($beneficiary, 'json', ['groups' => ['v3:me:read']]);
        }

        return $data;
    }

    /** @return array{violations: array<array{property_path: string, message: string}>} */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $formattedViolations = [];
        foreach ($violations as $violation) {
            $formattedViolations[] = ['property_path' => $violation->getPropertyPath(), 'message' => (string) $violation->getMessage()];
        }

        return ['violations' => $formattedViolations];
    }
}
