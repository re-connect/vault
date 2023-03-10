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
            'form' => $this->createForm(FilterBeneficiaryType::class, null, [
                'action' => $this->generateUrl('search_beneficiaries'),
                'attr' => ['data-controller' => 'ajax-list-filter'],
            ]),
        ]);
    }

    #[Route(
        path: '/professional/beneficiaries/search',
        name: 'search_beneficiaries',
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('ROLE_MEMBRE')]
    public function searchBeneficiaries(Request $request, BeneficiaireRepository $repository): Response
    {
        $formData = $request->request->all()['filter_beneficiary'];

        return new JsonResponse([
            'html' => $this->renderForm('v2/professional/_beneficiary_list.html.twig', [
                'beneficiaries' => $repository->filterByAuthorizedProfessional(
                    $this->getUser()->getSubject(),
                    $formData['search'],
                    $formData['relay'] ?? null,
                ),
                'form' => $this->createForm(FilterBeneficiaryType::class, null, [
                    'action' => $this->generateUrl('search_beneficiaries'),
                    'attr' => ['data-controller' => 'ajax-list-filter'],
                ]),
            ])->getContent(),
        ]);
    }
}
