<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\BeneficiaireCentre;
use App\Entity\Client;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\CentreManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\CentreProvider;
use App\Provider\UserProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: ['old' => '/api/', 'new' => '/api/v2/'], name: 're_api_centre_')]
class CentreRestV2Controller extends REController
{
    protected string $accessRead = Client::ACCESS_CENTRE_READ;
    protected string $accessWrite = Client::ACCESS_CENTRE_WRITE;
    protected string $accessDelete = Client::ACCESS_CENTRE_DELETE;

    #[Route(path: 'beneficiaries/{beneficiaryId}/centers', methods: ['GET'], requirements: ['beneficiaryId' => '\d{1,10}'], name: 'get_centres_from_beneficiaire')]
    public function getCentresFromBeneficiaire(
        int|string $beneficiaryId,
        CentreProvider $provider,
        BeneficiaireProvider $beneficiaireProvider
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, $this->accessRead);

            $entities = $provider->getCentresFromUserWithCentre($beneficiaire);

            return $this->json($entities);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'beneficiaries/{beneficiaryId}/pending-centers', methods: ['GET'], requirements: ['beneficiaryId' => '\d{1,10}'], name: 'get_pending_centres_from_beneficiaire')]
    public function getPendingCentresFromBeneficiaire(int|string $beneficiaryId, CentreProvider $provider, BeneficiaireProvider $beneficiaireProvider): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, $this->accessRead);

            $entities = $provider->getPendingCentresFromUserWithCentre($beneficiaire);

            return $this->json($entities);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'centers', name: 'list', methods: ['GET'])]
    public function getList(): Response
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
            } elseif ($user->isGestionnaire()) {
                $entities = $user->getCentres();
            }

            return $this->json($entities->toArray(), Response::HTTP_ACCEPTED);
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'centers/mine', name: 'list_mine', methods: ['GET'])]
    public function getMine(CentreProvider $provider): Response
    {
        $user = $this->getUser();
        $entities = !$user instanceof User ? [] : $provider->getEntitiesForUser($user)->toArray();

        return $this->json($entities);
    }

    #[Route(path: 'centers/waiting-ad', name: 'get_waiting_ad', methods: ['GET'])]
    public function getWaitingAd(): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            if (!$user->getSubject()->isBeneficiaire()) {
                throw $this->createAccessDeniedException('Il faut être bénéficiaire pour accéder à cette fonctionnalité');
            }

            $beneficiaireCentres = $user->getSubjectBeneficiaire()->getBeneficiairesCentres()->filter(static fn (BeneficiaireCentre $beneficiaireCentre) => false === $beneficiaireCentre->getBValid());

            $entities = [];
            foreach ($beneficiaireCentres as $beneficiaireCentre) {
                $entities[] = $beneficiaireCentre->getCentre();
            }

            return $this->json($entities);
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'centers/{id}/accept', methods: ['PATCH'], requirements: ['id' => '\d{1,10}'], name: 'accept')]
    public function accept(string $id, CentreProvider $provider, CentreManager $manager): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $entity = $provider->getEntity($id);

            $manager->accepterCentre($user->getSubject(), $entity);

            return $this->json($entity);
        } catch (AccessDeniedException|NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'centers/{id}/refuse', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'], name: 'refuse')]
    public function refuse(string $id, CentreProvider $provider, CentreManager $manager): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $entity = $provider->getEntity($id);

            $manager->refuserCentre($user->getSubject(), $entity);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (AccessDeniedException|NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'users/{userId}/centers/{id}/leave', methods: ['PATCH'], requirements: ['userId' => '\d{1,10}', 'id' => '\d{1,10}'], name: 'leave_center')]
    public function leaveCenter(string $userId, string $id, UserProvider $userProvider, CentreProvider $provider, CentreManager $manager): JsonResponse
    {
        try {
            if (!$this->getUser() instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $user = $userProvider->getEntity($userId);
            $entity = $provider->getEntity($id);

            $manager->deassociateUserWithCentres($user->getSubject(), $entity);

            return $this->json($entity, Response::HTTP_NO_CONTENT);
        } catch (AccessDeniedException|NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
