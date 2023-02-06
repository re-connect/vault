<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\Centre;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\CentreManager;
use App\Manager\RestManager;
use App\Provider\CentreProvider;
use App\Provider\MembreProvider;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   }, name="re_api_membre_")
 */
class MembreRestV2Controller extends REController
{
    /**
     * Get members for the connected user. (member/manager access) (access write) (grant_type: password).
     *
     * @Get("members",
     *     name="get_membres_from_user_handles_centre"
     * )
     */
    public function getMembresFromUserHandlesCentre(CentreProvider $centreProvider): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            if ($user->isMembre()) {
                $membre = $user->getSubjectMembre();
                $membresByCentre = $centreProvider->getOtherMembresFromMembre($membre);
            } elseif ($user->isGestionnaire()) {
                $gestionnaire = $user->getSubjectGestionnaire();
                $membresByCentre = $centreProvider->getMembresFromGestionnaire($gestionnaire);
            } else {
                throw new AccessDeniedException('You must be connected as member or manager.');
            }

            return $this->json($membresByCentre);
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Change right for a member given. (member access) (access write) (grant_type: password).
     *
     * @Patch("members/{id}/change-right",
     *     name="changer_droits_membre_centre"
     * )
     */
    public function changerDroitsMembreCentre(
        $id,
        Request $request,
        MembreProvider $provider,
        CentreManager $centreManager,
        RestManager $restManager
    ): JsonResponse {
        try {
            $entity = $provider->getEntity($id);

            if (null === $droit = $request->get('right')) {
                return $this->json($restManager->getErrorsToJson(['right' => 'Missing required parameters: right.']), Response::HTTP_BAD_REQUEST);
            }
            if (null === $centreName = $request->get('center_name')) {
                return $this->json($restManager->getErrorsToJson(['center_name' => 'Missing required parameters: center_name.']), Response::HTTP_BAD_REQUEST);
            }

            /** @var Centre $centre */
            $centre = $this->entityManager->getRepository(Centre::class)->findOneByNom($centreName);

            if (null === $centre) {
                throw new NotFoundHttpException('No center found for name '.$centreName);
            }

            $centreManager->switchDroitMembreCentre($entity, $centre, $droit);

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
