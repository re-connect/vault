<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function renderBeneficiaryNav(?Beneficiaire $beneficiary): Response
    {
        if ($subjectBeneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            return $this->render('v2/vault/nav/beneficiary/_beneficiary_nav.html.twig', ['beneficiary' => $subjectBeneficiary]);
        } elseif ($beneficiary) {
            return $this->render('v2/vault/nav/pro/_pro_nav.html.twig', ['beneficiary' => $beneficiary]);
        }

        return $this->render('void.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/beneficiary/', name: 'beneficiary_home', methods: ['GET'])]
    public function home(): Response
    {
        if (!$this->getUser()?->isBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }
        if ($subjectBeneficiary = $this->getUser()?->getSubject()) {
            return $this->render('v2/beneficiary/home/home.html.twig', ['beneficiary' => $subjectBeneficiary]);
        }

        return $this->render('void.html.twig');
    }
}
