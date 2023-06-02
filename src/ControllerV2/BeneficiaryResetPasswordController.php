<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
}
