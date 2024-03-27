<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Provider\BeneficiaireProvider;
use App\ServiceV2\Mailer\MailerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/api/v3/users', format: 'json')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route(path: '/request-personal-account-data', name: 'request_personal_account_data', methods: ['GET'])]
    public function requestPersonalAccountData(MailerService $mailer): Response
    {
        $user = $this->getUser();

        if (!$user->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->hasRequestedPersonalAccountData()) {
            $mailer->sendPersonalDataRequestEmail($user);
        }

        return $this->json($user);
    }

    #[Route(path: '/me', name: 'get_me', methods: ['GET'])]
    public function getMe(): JsonResponse
    {
        return $this->json($this->getUser());
    }

    #[Route(path: '/secret-questions', name: 'get_secret_questions', methods: ['GET'])]
    public function getSecretQuestions(BeneficiaireProvider $provider, TranslatorInterface $translator): JsonResponse
    {
        return $this->json($provider->getSecretQuestionsV2($translator));
    }
}
