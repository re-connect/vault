<?php

namespace App\Controller;

use App\Entity\User;
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

    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    public function loginEnd(Request $request): RedirectResponse
    {
        $route = match (true) {
            !$this->getUser() => 're_main_login',
            !$this->getUser()->isBeneficiaire() && $this->gdprService->isPasswordRenewalDue() => 'app_update_password',
            !$this->isGranted(User::USER_TYPE_ADMINISTRATEUR) && $request->getSession()->get('_security.main.target_path') => $request->getSession()->get('_security.main.target_path'),
            default => 're_user_redirectUser',
        };

        return $this->redirect($this->generateUrl($route));
    }

    public function redirectUser(): ?RedirectResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || is_string($user)) {
            return $this->redirect($this->generateUrl('re_main_login'));
        }

        if ($user->isAdministrateur()) {
            return $this->redirect($this->generateUrl('sonata_admin_dashboard'));
        }
        if ($user->getFirstVisit()) {
            return $this->redirect($this->generateUrl('re_user_firstVisit'));
        }

        if ($user->isBeneficiaire()) {
            if (null === $user->getSubjectBeneficiaire()->getQuestionSecrete() || null === $user->getSubjectBeneficiaire()->getReponseSecrete()) {
                return $this->redirect($this->generateUrl('re_beneficiaire_setQuestionSecrete'));
            }

            return $this->redirect($this->generateUrl('re_beneficiaire_accueil'));
        }

        if ($user->isMembre() || $user->isGestionnaire()) {
            return $this->redirect($this->generateUrl('list_beneficiaries'));
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
