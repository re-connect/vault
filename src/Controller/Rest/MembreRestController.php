<?php

namespace App\Controller\Rest;

use App\Controller\AbstractController;
use App\Entity\Membre;
use App\Exception\JsonResponseException;
use App\Manager\CentreManager;
use App\Provider\CentreProvider;
use App\Repository\CentreRepository;
use App\Security\Authorization\Voter\MembreVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/appli")
 */
class MembreRestController extends AbstractController
{
    /**
     * @Route(
     *     "/membres",
     *     name="api_get_membres_from_user_handles_centre",
     *     options={"expose"=true},
     *     methods={"GET"}
     * )
     */
    public function getMembresFromUserHandlesCentreAction(CentreProvider $centreProvider): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user->isGestionnaire()) {
                $gestionnaire = $user->getSubjectGestionnaire();
                $membresByCentre = $centreProvider->getMembresFromGestionnaire($gestionnaire);
            } elseif ($user->isMembre()) {
                $membre = $user->getSubjectMembre();
                $membresByCentre = $centreProvider->getOtherMembresFromMembre($membre);
            } else {
                throw new \RuntimeException('You must be connected as membre or gestionnaire');
            }

            return $this->json($membresByCentre, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/membres/{id}/changer-droit",
     *     name="api_changer_droits_membre_centre",
     *     options={"expose"=true},
     *     methods={"POST"}
     * )
     */
    public function changerDroitsMembreCentreAction(Membre $membre, Request $request, CentreManager $centreManager, CentreRepository $centreRepository): Response
    {
        if (false === $this->isGranted(MembreVoter::GESTION_MEMBRE, $membre)) {
            $this->createAccessDeniedException("Vous n'avez pas le droit de changer les droits de ce membre");
        }

        $centreName = $request->get('centreName');
        $droit = $request->get('droit');

        $centre = $centreRepository->findOneByNom($centreName);

        if (null === $centre) {
            throw new \RuntimeException('Aucun centre trouvé pour ce nom');
        }

        $centreManager->switchDroitMembreCentre($membre, $centre, $droit);

        return new Response();
    }
}
