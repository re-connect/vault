<?php

namespace App\ControllerV2;

use App\Domain\MFA\MfaCodeSender;
use App\Entity\User;
use App\Security\VoterV2\BeneficiaryVoter;
use App\ServiceV2\GdprService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public function __construct(private readonly GdprService $gdprService)
    {
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): Response
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    #[Route(path: '/login-end', name: 'login_end', methods: ['GET'])]
    public function loginEnd(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $session = $request->getSession();

        $session->set('_locale', $user?->getLastLang() ?? User::DEFAULT_LANGUAGE);
        $targetPath = $session->get('_security.main.target_path');

        return $this->redirect(match (true) {
            !$this->getUser() => $this->generateUrl('re_main_login'),
            !$this->getUser()->isBeneficiaire() && $this->gdprService->isPasswordRenewalDue() => $this->generateUrl('app_update_password'),
            !$this->isGranted(User::USER_TYPE_ADMINISTRATEUR) && $targetPath => $targetPath,
            default => $this->generateUrl('redirect_user'),
        });
    }

    #[Route(path: '/user/redirect-user/', name: 'redirect_user', methods: ['GET'])]
    public function redirectUser(): ?RedirectResponse
    {
        $user = $this->getUser();

        return match (true) {
            $user?->isAdministrateur() || $user?->isSuperAdmin() => $this->redirectToRoute('sonata_admin_dashboard'),
            $user?->isFirstVisit() => $this->redirectToRoute('user_first_visit'),
            $user?->isBeneficiaire() => $this->redirectToRoute('beneficiary_home'),
            $user?->isMembre() => $this->redirect($this->generateUrl($this->isGranted(BeneficiaryVoter::MANAGE) ? 'list_beneficiaries' : 'affiliate_beneficiary_home')),
            true => $this->redirectToRoute('re_main_login'),
        };
    }

    #[Route('/login_link', name: 'login_link')]
    public function loginLink(): Response
    {
        throw new \LogicException('This code should never be reached');
    }

    #[Route('/resend-auth-code', name: 'resend_auth_code', methods: ['GET'])]
    public function resendAuthCode(MfaCodeSender $mfaCodeSender): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('re_main_login');
        }

        if ($mfaCodeSender->sendCode($user)) {
            $this->addFlash('success', 'mfa_new_code_sent');
        }

        return $this->redirectToRoute('2fa_login');
    }
}
