<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Routes about the connected user. They have a priority, otherwise API Platform's /api/v3/users/{id} routes would match "me".
 */
#[Route(path: '/api/v3/users/me', format: 'json')]
#[IsGranted('ROLE_USER')]
final class MeApiController extends AbstractController
{
    #[Route(path: '', methods: ['GET'], priority: 1)]
    public function me(NormalizerInterface $normalizer): JsonResponse
    {
        $user = $this->getUser();
        $data = [
            ...$normalizer->normalize($user, 'json', ['groups' => ['v3:user:read']]),
            'subject_id' => $user->getSubject()?->getId(),
        ];

        $beneficiary = $user->isBeneficiaire() ? $user->getSubjectBeneficiaire() : null;
        if ($beneficiary) {
            $data['beneficiary'] = $normalizer->normalize($beneficiary, 'json', ['groups' => ['v3:me:read']]);
        }

        return $this->json($data);
    }
}
