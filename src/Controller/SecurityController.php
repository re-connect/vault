<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\VoterV2\BeneficiaryVoter;
use App\ServiceV2\GdprService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    public function __construct(private readonly GdprService $gdprService)
    {
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
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

        if (!$user instanceof User || is_string($user)) {
            return $this->redirect($this->generateUrl('re_main_login'));
        }

        if ($user->isAdministrateur()) {
            return $this->redirect($this->generateUrl('sonata_admin_dashboard'));
        }
        if ($user->isFirstVisit()) {
            return $this->redirect($this->generateUrl('user_first_visit'));
        }

        if ($user->isBeneficiaire()) {
            return $this->redirect($this->generateUrl('beneficiary_home'));
        }

        if ($user->isMembre() || $user->isGestionnaire()) {
            return $this->redirect($this->generateUrl($this->isGranted(BeneficiaryVoter::MANAGE) ? 'list_beneficiaries' : 'affiliate_beneficiary_home'));
        }

        if ($user->isAssociation()) {
            return $this->redirect($this->generateUrl('re_association_accueil'));
        }

        return $this->redirect($this->generateUrl('re_main_login'));
    }

    #[Route('/login_link', name: 'login_link')]
    public function loginLink()
    {
        throw new \LogicException('This code should never be reached');
    }
}
