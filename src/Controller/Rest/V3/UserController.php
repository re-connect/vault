<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Provider\BeneficiaireProvider;
use App\ServiceV2\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/api/v3/users', format: 'json')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{

    #[Route(path: '/me', methods: ['GET'])]
    public function getMe(): JsonResponse
    {
        return $this->json($this->getUser());
    }

    #[Route(path: '/me/request-personal-account-data', methods: ['GET'])]
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

    #[Route(path: '/me/register-notification-token', methods: ['PUT'])]
    public function setFcnToken(Request $request, EntityManagerInterface $em): JsonResponse
    {
            $user = $this->getUser()->setFcnToken($request->request->get('notification_token'));
            $em->flush();

            return $this->json($user);
    }

    #[Route(path: '/public/secret-questions', methods: ['GET'])]
    public function getSecretQuestions(BeneficiaireProvider $provider, TranslatorInterface $translator): JsonResponse
    {
        return $this->json($provider->getSecretQuestionsV2($translator));
    }
}
