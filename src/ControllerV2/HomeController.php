<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function renderBeneficiaryNav(?Beneficiaire $beneficiary, RequestStack $stack): Response
    {
        if ($subjectBeneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            return $this->render('v2/vault/nav/beneficiary/_beneficiary_nav.html.twig', ['beneficiary' => $subjectBeneficiary, 'route' => $stack->getMainRequest()->attributes->get('_route')]);
        } elseif ($beneficiary) {
            return $this->render('v2/vault/nav/pro/_pro_nav.html.twig', ['beneficiary' => $beneficiary]);
        }

        return $this->render('void.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/beneficiary', name: 'beneficiary_home', methods: ['GET'])]
    public function home(): Response
    {
        if (!$subjectBeneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('v2/beneficiary/home/home.html.twig', ['beneficiary' => $subjectBeneficiary]);
    }
}
