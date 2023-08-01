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

    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    public function loginEnd(Request $request): RedirectResponse
    {
        $targetPath = $request->getSession()->get('_security.main.target_path');

        return $this->redirect(match (true) {
            !$this->getUser() => $this->generateUrl('re_main_login'),
            !$this->getUser()->isBeneficiaire() && $this->gdprService->isPasswordRenewalDue() => $this->generateUrl('app_update_password'),
            !$this->isGranted(User::USER_TYPE_ADMINISTRATEUR) && $targetPath => $targetPath,
            default => $this->generateUrl('re_user_redirectUser'),
        });
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
        if ($user->isFirstVisit()) {
            return $this->redirect($this->generateUrl('re_user_firstVisit'));
        }

        if ($user->isBeneficiaire()) {
            // I think we never pass this condition
            if ((!$user->getSubjectBeneficiaire()->getQuestionSecrete() || !$user->getSubjectBeneficiaire()->getReponseSecrete()) && $user->isFirstVisit()) {
                return $this->redirect($this->generateUrl('re_beneficiaire_setQuestionSecrete'));
            }

            return $this->redirect($this->generateUrl('beneficiary_home'));
        }

        if ($user->isMembre() || $user->isGestionnaire()) {
            return $this->redirect($this->generateUrl($this->isGranted(BeneficiaryVoter::MANAGE) ? 'list_beneficiaries' : 're_membre_ajoutBeneficiaire'));
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
