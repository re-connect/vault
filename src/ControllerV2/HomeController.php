<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function renderBeneficiaryNav(?Beneficiaire $beneficiary, ?string $currentRoute = ''): Response
    {
        if ($subjectBeneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            return $this->render('v2/vault/nav/beneficiary/_beneficiary_nav.html.twig', ['beneficiary' => $subjectBeneficiary, 'route' => $currentRoute]);
        } elseif ($beneficiary) {
            return $this->render('v2/vault/nav/pro/_pro_nav.html.twig', ['beneficiary' => $beneficiary]);
        }

        return $this->render('void.html.twig');
    }
}
