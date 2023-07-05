<?php

namespace App\Controller;

use App\Entity\Centre;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Manager\CentreManager;
use App\Provider\CentreProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/membre")
 */
class MembreController extends AbstractController
{
    /**
     * @Route("/centres", name="re_membre_centres", methods={"GET"})
     */
    public function centres(CentreProvider $centreProvider): Response
    {
        $subject = $this->getUser()->getSubject();
        $centres = $centreProvider->getCentresFromUserWithCentre($subject);
        $membreCentres = $subject instanceof Membre ? $subject->getMembresCentres() : [];
        $membreCentresById = [];
        foreach ($membreCentres as $membreCentre) {
            $membreCentresById[$membreCentre->getCentre()->getId()] = $membreCentre;
        }

        return $this->render('user/membre/centres.html.twig', [
            'centres' => $centres,
            'subject' => $subject,
            'membreCentres' => $membreCentresById,
            'isGestionnaire' => $subject instanceof Gestionnaire,
        ]);
    }

    /**
     * @Route(
     *     "/quitter-centre/{id}",
     *     name="re_membre_quitterCentre",
     *     methods={"GET"},
     *      requirements={
     *          "id": "\d{1,10}"
     *     }
     *     )
     */
    public function quitterCentre(Centre $centre, Request $request, CentreManager $centreManager): RedirectResponse
    {
        try {
            $centreManager->deassociateUserWithCentres($this->getUser()->getSubjectMembre(), $centre);
            $this->addFlash('success', 'membre.centres.vousAvezBienQuitte');
        } catch (\Exception $e) {
            $this->addFlash('error', 'an_error_occurred');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
