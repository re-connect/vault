<?php

namespace App\Controller;

use App\Entity\Centre;
use App\Provider\CentreProvider;
use App\Security\Authorization\Voter\CentreVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GestionnaireController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function beneficiaires(): Response
    {
        return $this->render('user/membre-beneficiaire/beneficiaires.html.twig');
    }

    public function membres(CentreProvider $centreProvider): RedirectResponse
    {
        $centres = $centreProvider->getCentresFromGestionnaire($this->getUser()->getSubjectGestionnaire());

        return $this->redirect($this->generateUrl('re_gestionnaire_membresCentre', ['id' => $centres[0]->getId()]));
    }

    public function membresCentre(Centre $centre): Response
    {
        if (false === $this->authorizationChecker->isGranted(CentreVoter::GESTION_CENTRE, $centre)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de gérer ce centre'");
        }

        return $this->render('user/membre-membre/membresCentre.html.twig', [
            'centre' => $centre,
        ]);
    }
}
