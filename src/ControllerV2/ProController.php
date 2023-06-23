<?php

namespace App\ControllerV2;

use App\FormV2\Search\SearchFormModel;
use App\FormV2\Search\SearchType;
use App\Repository\MembreRepository;
use App\Security\VoterV2\ProVoter;
use App\ServiceV2\PaginatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/pro')]
class ProController extends AbstractController
{
    #[Route(path: '/create/home', name: 'create_pro_home', methods: ['GET'])]
    #[IsGranted(ProVoter::MANAGE)]
    public function createProHome(): Response
    {
        $form = $this->createForm(SearchType::class, new SearchFormModel(), [
            'action' => $this->generateUrl('search_pro'),
        ]);

        return $this->render('v2/pro/create_index.html.twig', ['form' => $form]);
    }

    #[Route(path: '/search', name: 'search_pro', methods: ['GET', 'POST'])]
    #[IsGranted(ProVoter::MANAGE)]
    public function searchPros(Request $request, MembreRepository $repository, PaginatorService $paginator): Response
    {
        $search = new SearchFormModel($request->query->get('q'));
        $form = $this->createForm(SearchType::class, $search, [
            'action' => $this->generateUrl('search_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('search_pro', ['q' => $search->getSearch()]);
        }

        $pros = $paginator->create(
            $repository->search($search->getSearch()),
            $request->query->getInt('page', $request->query->getInt('page', 1)),
        );

        return $request->isXmlHttpRequest()
            ? $this->render('v2/pro/_search_results_card.html.twig', ['pros' => $pros])
            : $this->render('v2/pro/search.html.twig', ['form' => $form, 'pros' => $pros]);
    }
}
