<?php

namespace App\ControllerV2;

use App\FormV2\FilterBeneficiaryType;
use App\Repository\BeneficiaireRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfessionalController extends AbstractController
{
    #[Route(path: '/professional/beneficiaries', name: 'list_beneficiaries', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function listBeneficiaries(BeneficiaireRepository $repository): Response
    {
        return $this->renderForm('v2/professional/beneficiaries.html.twig', [
            'beneficiaries' => $repository->findByAuthorizedProfessional($this->getUser()->getSubject()),
            'form' => $this->createForm(FilterBeneficiaryType::class),
        ]);
    }

    #[Route(
        path: '/professional/beneficiaries/search',
        name: 'search_beneficiaries',
        methods: ['GET'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('ROLE_MEMBRE')]
    public function searchBeneficiaries(Request $request, BeneficiaireRepository $repository): Response
    {
        return new JsonResponse([
            'html' => $this->renderForm('v2/professional/beneficiary_list.html.twig', [
                'beneficiaries' => $repository->filterByAuthorizedProfessional(
                    $this->getUser()->getSubject(),
                    $request->query->get('word', '')
                ),
                'form' => $this->createForm(FilterBeneficiaryType::class),
            ])->getContent(),
        ]);
    }
}
