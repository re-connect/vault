<?php

namespace App\ControllerV2;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OldRoutesController extends AbstractController
{
    #[Route(path: '/beneficiaire/')]
    #[Route(path: '/beneficiaire/set-question-secrete')]
    #[Route(path: '/appli/beneficiaire/{id}/dossier/ajouter')]
    #[Route(path: '/appli/beneficiaire/{id}/document')]
    #[Route(path: '/appli/beneficiaire/{id}/note')]
    #[Route(path: '/appli/beneficiaire/{id}/note/ajouter')]
    #[Route(path: '/appli/beneficiaire/{id}/evenement')]
    #[Route(path: '/appli/beneficiaire/{id}/evenement/ajouter')]
    #[Route(path: '/appli/beneficiaire/{id}/contact')]
    #[Route(path: '/appli/beneficiaire/{id}/contact/ajouter')]
    public function redirectV1BeneficiaryRoutes(): Response
    {
        return $this->redirectUser();
    }

    #[Route(path: '/membre/beneficiaires')]
    #[Route(path: '/membre/membres')]
    #[Route(path: '/membre/centres')]
    #[Route(path: '/membre/quitter-centre/{id}')]
    #[Route(path: '/membre/membres/{id}')]
    #[Route(path: '/membre/membres/creation-membre')]
    #[Route(path: '/membre/membres/{id}/username')]
    #[Route(path: '/membre/membres/ajout-membres/{id}')]
    #[Route(path: '/membre/membres/ajout-membres/rechercher')]
    #[Route(path: '/membre/membres/ajout-membres/{id}/termine')]
    #[Route(path: '/membre/membres/{id}/supprimer-du-centre')]
    #[Route(path: '/membre/membres/ajout-membres/{id}/envoyer-sms')]
    #[Route(path: '/membre/beneficiaires/ajout-beneficiaires/{id}')]
    #[Route(path: '/membre/beneficiaires/ajout-beneficiaires/{id}/envoyer-sms')]
    #[Route(path: '/membre/beneficiaires/ajout-beneficiaires/{id}/termine')]
    #[Route(path: '/membre/beneficiaires/ajout-beneficiaires/{id}/question-secrete')]
    #[Route(path: '/membre/beneficiaires/ajout-beneficiaires/{id}/question-secrete')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/etape-2')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/etape-3')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/{way}/etape-1')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/{way}/etape-4')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/{way}/etape-5')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/{way}/etape-6/{id}')]
    #[Route(path: '/membre/beneficiaires/creation-beneficiaire/reset')]
    public function redirectV1ProRoutes(): Response
    {
        return $this->redirectUser();
    }

    #[Route(path: '/user/{id}/settings')]
    #[Route(path: '/user/accepter-centre/{id}')]
    #[Route(path: '/user/refuser-centre/{id}')]
    public function redirectV1UserRoutes(): Response
    {
        return $this->redirectUser();
    }

    private function redirectUser(): Response
    {
        return $this->redirectToRoute('redirect_user');
    }
}
