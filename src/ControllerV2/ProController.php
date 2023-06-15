<?php

namespace App\ControllerV2;

use App\FormV2\Search\SearchFormModel;
use App\FormV2\Search\SearchType;
use App\Security\VoterV2\ProVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/pro')]
class ProController extends AbstractController
{
    #[Route(path: '/create/home', name: 'create_pro_home', methods: ['GET'])]
    #[IsGranted(ProVoter::MANAGE)]
    public function listBeneficiaries(): Response
    {
        $form = $this->createForm(SearchType::class, new SearchFormModel());

        return $this->render('v2/pro/create_index.html.twig', ['form' => $form]);
    }
}
