<?php

namespace App\ControllerV2;

use App\FormV2\FilterBeneficiaryType;
use App\Repository\BeneficiaireRepository;
use App\Repository\MembreRepository;
use App\ServiceV2\PaginatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/professional')]
#[IsGranted('ROLE_MEMBRE')]
class ProfessionalController extends AbstractController
{
    private const PAGINATION_RESULTS_LIMIT = 10;

    #[Route(path: '/beneficiaries', name: 'list_beneficiaries', methods: ['GET'])]
    public function listBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        return $this->render('v2/professional/beneficiaries/beneficiaries.html.twig', [
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
        path: '/beneficiaries/search',
        name: 'search_beneficiaries',
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    public function searchBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        $form = $this->createForm(FilterBeneficiaryType::class, null, [
            'action' => $this->generateUrl('search_beneficiaries'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
        ])->handleRequest($request);

        return new JsonResponse([
            'html' => $this->render('v2/professional/beneficiaries/_beneficiaries_list.html.twig', [
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

    #[Route(path: '/professionals', name: 'list_professionals', methods: ['GET'])]
    public function listProfessionals(Request $request, MembreRepository $repository, PaginatorService $paginator): Response
    {
        return $this->render('v2/professional/professionals/professionals.html.twig', [
            'professionals' => $paginator->create(
                $repository->findByAuthorizedProfessional($this->getUser()->getSubject()),
                $request->query->getInt('page', 1),
                self::PAGINATION_RESULTS_LIMIT,
            ),
            'form' => $this->createForm(FilterBeneficiaryType::class),
        ]);
    }
}
