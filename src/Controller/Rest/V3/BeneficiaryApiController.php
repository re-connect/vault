<?php

namespace App\Controller\Rest\V3;

use App\Controller\Rest\V3\Dto\EnableBeneficiaryDto;
use App\ControllerV2\AbstractController;
use App\ManagerV2\UserManager;
use App\Validator\Constraints\PasswordCriteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/api/v3/beneficiaries', format: 'json')]
#[IsGranted('ROLE_USER')]
final class BeneficiaryApiController extends AbstractController
{
    private const string OTHER_SECRET_QUESTION = 'Autre';

    /**
     * Activates the account of the connected beneficiary: secret question, password and, optionally, email.
     */
    #[Route(path: '/me/enable', methods: ['PUT'])]
    public function enable(
        UserManager $userManager,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        #[MapRequestPayload] EnableBeneficiaryDto $dto,
    ): Response {
        $user = $this->getUser();
        if (!$user->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }
        $beneficiary = $user->getSubjectBeneficiaire();

        if ($dto->email) {
            $user->setEmail($dto->email);
        }
        $secretQuestion = $dto->otherSecretQuestion ?: $dto->secretQuestion;
        $beneficiary
            ->setQuestionSecrete(self::OTHER_SECRET_QUESTION === $secretQuestion ? null : $secretQuestion)
            ->setReponseSecrete($dto->secretAnswer);

        $violations = $validator->validate($user, null, ['beneficiaire']);
        $violations->addAll($validator->validate($beneficiary, null, ['beneficiaireQuestionSecrete']));
        foreach ($validator->validate($dto->password, new PasswordCriteria($user)) as $violation) {
            $violations->add(new ConstraintViolation($violation->getMessage(), $violation->getMessageTemplate(), $violation->getParameters(), $dto, 'password', null));
        }

        if (count($violations) > 0) {
            // Discard the invalid changes, so that no later flush can save them
            $em->refresh($user);
            $em->refresh($beneficiary);

            throw new UnprocessableEntityHttpException((string) $violations, new ValidationFailedException($dto, $violations));
        }

        $userManager->updatePassword($user, $dto->password);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
