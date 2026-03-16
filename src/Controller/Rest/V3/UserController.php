<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\User;
use App\ServiceV2\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/v3/user', name: 'api_user_')]
class UserController extends AbstractController
{
    #[Route(path: '/request-personal-account-data', name: 'request_personal_account_data', methods: ['GET'])]
    public function requestPersonalAccountData(MailerService $mailer, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->hasRequestedPersonalAccountData()) {
            $mailer->sendPersonalDataRequestEmail($user);
            $user->setPersonalAccountDataRequestedAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }
}
