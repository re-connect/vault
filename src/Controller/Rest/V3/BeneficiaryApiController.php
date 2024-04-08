<?php

namespace App\Controller\Rest\V3;

use App\Controller\Rest\V3\Dto\EnableBeneficiaryDto;
use App\ControllerV2\AbstractController;
use App\ManagerV2\UserManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/v3/beneficiaries', format: 'json')]
#[IsGranted('ROLE_USER')]
final class BeneficiaryApiController extends AbstractController
{
    #[Route(path: '/me/enable', methods: ['PUT'])]
    public function enable(UserManager $userManager, #[MapRequestPayload] EnableBeneficiaryDto $dto): Response
    {
        $user = $this->getUser();
        if (!$user->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }
        $user->setEmail($dto->email ?? $user->getEmail());
        $user->getSubjectBeneficiaire()
            ->setQuestionSecrete($dto->otherSecretQuestion ?? $dto->secretQuestion)
            ->setReponseSecrete($dto->secretAnswer);

        $userManager->updatePassword($user, $dto->password);

        return $this->json($user->getSubjectBeneficiaire());
    }
}
