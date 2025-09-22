<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\ManagerV2\UserManager;
use App\Provider\BeneficiaireProvider;
use App\ServiceV2\Helper\PasswordHelper;
use App\ServiceV2\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/api/v3/users', format: 'json')]
#[IsGranted('ROLE_USER')]
class UserApiController extends AbstractController
{
    #[Route(path: '/request-personal-account-data', methods: ['GET'])]
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

        return $this->json($user, 200, [], ['groups' => ['v3:user:read']]);
    }

    #[Route(path: '/me/register-notification-token', methods: ['POST'])]
    public function setFcnToken(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser()->setFcnToken($request->request->get('notification_token'));
        $em->flush();

        return $this->json($user, 200, [], ['groups' => ['v3:user:read']]);
    }

    #[Route(path: '/me/switch-locale', methods: 'POST')]
    public function switch(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $newLocale = $request->request->get('locale');
        $user = $this->getUser();
        if ($user && $newLocale) {
            $user->setLastLang($newLocale);
            $em->flush();
        }

        return $this->json($user, 200, [], ['groups' => ['v3:user:read']]);
    }

    #[Route(path: '/me/update_password', methods: ['POST'])]
    public function updatePassword(Request $request, UserManager $userManager, PasswordHelper $helper): JsonResponse
    {
        $user = $this->getUser();
        $password = $request->request->get('password');
        if (!$password) {
            return $this->json(['password' => 'missing'], Response::HTTP_BAD_REQUEST);
        } elseif (!$helper->isStrongPassword($user, $password)) {
            return $this->json(['password' => 'weak'], Response::HTTP_BAD_REQUEST);
        }

        $userManager->updatePassword($user, $password);

        return $this->json($user, 200, [], ['groups' => ['v3:user:read']]);
    }

    #[Route(path: '/public/secret-questions', methods: ['GET'])]
    public function getSecretQuestions(BeneficiaireProvider $provider, TranslatorInterface $translator): JsonResponse
    {
        return $this->json($provider->getSecretQuestionsV2($translator));
    }
}
