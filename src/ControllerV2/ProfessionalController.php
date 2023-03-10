<?php

namespace App\ControllerV2;

use App\FormV2\FilterBeneficiaryType;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\PaginatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfessionalController extends AbstractController
{
    private const PAGINATION_RESULTS_LIMIT = 10;

    #[Route(path: '/professional/beneficiaries', name: 'list_beneficiaries', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function listBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        return $this->renderForm('v2/professional/beneficiaries.html.twig', [
            'beneficiaries' => $paginator->create(
                $repository->findByAuthorizedProfessional($this->getUser()->getSubject()),
                $request->query->getInt('page', 1),
                self::PAGINATION_RESULTS_LIMIT,
            ),
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
    public function searchBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        $form = $this->createForm(FilterBeneficiaryType::class, null, [
            'action' => $this->generateUrl('search_beneficiaries'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
        ])->handleRequest($request);

        return new JsonResponse([
            'html' => $this->renderForm('v2/professional/_beneficiary_list.html.twig', [
                'beneficiaries' => $paginator->create(
                    $repository->filterByAuthorizedProfessional(
                        $this->getUser()->getSubject(),
                        $form->get('search')->getData(),
                        $form->get('relay')->getData(),
                    ),
                    $request->query->getInt('page', 1),
                    self::PAGINATION_RESULTS_LIMIT,
                ),
                'form' => $form,
            ])->getContent(),
        ]);
    }
}
