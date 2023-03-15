<?php

namespace App\Controller\Rest;

use App\Entity\BeneficiaireCentre;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\CentreManager;
use App\Provider\CentreProvider;
use App\Provider\UserProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/appli", name="api_center_")
 */
final class CentreRestController extends AbstractController
{
    private CentreProvider $provider;
    private CentreManager $manager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityManagerInterface $em;

    public function __construct(CentreProvider $provider, CentreManager $manager, AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $em)
    {
        $this->provider = $provider;
        $this->manager = $manager;
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
    }

    /**
     * @Get("/centers")
     *
     * @throws \Exception
     */
    public function listAction(): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            $entities = new ArrayCollection();
            if ($user->isBeneficiaire()) {
                $beneficiaire = $user->getSubjectBeneficiaire();
                $entities = $beneficiaire->getBeneficiairesCentres();
            } elseif ($user->isMembre()) {
                $entities = $user->getSubjectMembre()->getMembresCentres();
            }

            return $this->json($entities->toArray(), Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Get("/centers/waiting-ad")
     */
    public function getWaitingAdAction(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user instanceof User || !$user->getSubject()->isBeneficiaire()) {
                throw new AccessDeniedException('Il faut être bénéficiaire pour accéder à cette fonctionnalité');
            }

            $beneficiaire = $user->getSubjectBeneficiaire();
            /** @var BeneficiaireCentre[] $beneficiaireCentres */
            $beneficiaireCentres = $beneficiaire->getBeneficiairesCentres()->filter(static function (BeneficiaireCentre $beneficiaireCentre) {
                return false === $beneficiaireCentre->getBValid();
            });
            $entities = [];
            foreach ($beneficiaireCentres as $beneficiaireCentre) {
                $entities[] = $beneficiaireCentre->getCentre();
            }

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Rest\Patch("/centers/{id}/accept",
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function acceptAction($id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id);

            $this->manager->accepterCentre($this->getUser()->getSubject(), $entity);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Rest\Patch("/centers/{id}/refuse",
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function refuseAction($id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id);

            $this->manager->refuserCentre($this->getUser()->getSubject(), $entity);

            return $this->json($entity, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Rest\Patch("/users/{userId}/centers/{id}/leave",
     *     requirements={
     *          "userId": "\d{1,10}",
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function leaveCenterAction($userId, $id, UserProvider $userProvider): JsonResponse
    {
        try {
            $user = $userProvider->getEntity($userId);
            $entity = $this->provider->getEntity($id);

            $this->manager->deassociateUserWithCentres($user->getSubject(), $entity);

            return $this->json($entity, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
