<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\User;
use App\ServiceV2\Mailer\MailerService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/v3/users', name: 'api_user_')]
class UserController extends AbstractController
{
    #[Route(path: '/request-personal-account-data', name: 'request_personal_account_data', methods: ['GET'])]
    public function requestPersonalAccountData(MailerService $mailer): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->hasRequestedPersonalAccountData()) {
            $mailer->sendPersonalDataRequestEmail($user);
        }

        return $this->json($user);
    }
}
