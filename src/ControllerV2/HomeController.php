<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function renderBeneficiaryNav(?Beneficiaire $beneficiary): Response
    {
        $user = $this->getUser();
        if ($subjectBeneficiary = $user->getSubjectBeneficiaire()) {
            $beneficiary = $subjectBeneficiary;

            return $this->render('v2/vault/nav/beneficiary/_beneficiary_nav.html.twig', [
                'beneficiary' => $beneficiary,
            ]);
        }

        return $user->getSubjectMembre() && $beneficiary
            ? $this->render('v2/vault/nav/pro/_pro_nav.html.twig', ['beneficiary' => $beneficiary])
            : $this->render('void.html.twig');
    }
}
