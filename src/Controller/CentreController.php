<?php

namespace App\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Manager\CentreManager;
use App\Provider\CentreProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CentreController extends AbstractController
{
    #[Security("is_granted('SELF_EDIT', beneficiaire.getUser())")]
    public function centres(
        Beneficiaire $beneficiaire,
        AuthorizationCheckerInterface $authorizationChecker,
        CentreProvider $centreProvider
    ): Response {
        $centres = $centreProvider->getCentresFromUserWithCentre($beneficiaire);

        return $this->render('app/centre/centres.html.twig', [
            'centres' => $centres,
            'beneficiaire' => $beneficiaire,
        ]);
    }

    /**
     * @ParamConverter("centre", options={"id" = "centreId"})
     *
     * @throws \Exception
     */
    #[Security("is_granted('SELF_EDIT', beneficiaire.getUser())")]
    public function quitterCentre(Beneficiaire $beneficiaire, Centre $centre, Request $request, CentreManager $centreManager, RequestStack $requestStack): Response
    {
        if ($request->get('quitter')) {
            // La vérif de securité est faite au niveau du manager de centres
            $centreManager->deassociateUserWithCentres($beneficiaire, $centre);
            $session = $requestStack->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->set('success', 'centre.vousAvezBienQuitte');
            }

            if ($this->getUser()->getId() === $beneficiaire->getUser()->getId()) {
                return $this->redirect($this->generateUrl('re_app_centres', ['id' => $beneficiaire->getId()]));
            }

            return $this->redirect($this->generateUrl('re_user_redirectUser'));
        }

        return $this->render('app/centre/quitter_centre.html.twig', [
            'centre' => $centre,
            'beneficiaire' => $beneficiaire,
        ]);
    }
}
