<?php

namespace App\ControllerV2;

use App\FormV2\Search\SearchFormModel;
use App\FormV2\Search\SearchType;
use App\Security\VoterV2\ProVoter;
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
    public function searchPros(Request $request): Response
    {
        $search = new SearchFormModel($request->query->getAlpha('q'));
        $form = $this->createForm(SearchType::class, $search, [
            'action' => $this->generateUrl('search_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('search_pro', ['q' => $search->getSearch()]);
        }

        return $this->render('v2/pro/search.html.twig', ['form' => $form]);
    }
}
