<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\ServiceV2\ResettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_MEMBRE')]
class BeneficiaryResetPasswordController extends AbstractController
{
    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/beneficiary/{id}/reset-password', name: 'reset_password_beneficiary', methods: ['GET'])]
    public function choice(Beneficiaire $beneficiary): Response
    {
        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/beneficiary/{id}/reset-password/email', name: 'reset_password_beneficiary_email', methods: ['GET'])]
    public function resetEmail(Request $request, Beneficiaire $beneficiary, ResettingService $service): Response
    {
        if ($email = $beneficiary->getUser()->getEmail()) {
            $service->processSendingPasswordResetEmail($email, $request->getLocale());
        } else {
            $this->addFlash('error', 'beneficiary_has_no_email');
        }

        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }
}
