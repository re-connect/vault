<?php

namespace App\ControllerV2;

use App\Repository\BeneficiaireRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfessionalController extends AbstractController
{
    #[Route(path: '/professional/beneficiaries', name: 'list_beneficiaries', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function list(BeneficiaireRepository $repository): Response
    {
        return $this->render('v2/professional/beneficiaries.html.twig', [
            'beneficiaries' => $repository->findByAuthorizedProfessional($this->getUser()->getSubject()),
        ]);
    }
}
