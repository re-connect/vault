<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class DonneePersonnelleRestController extends REController
{
    protected $provider;
    protected RestManager $restManager;
    protected BeneficiaireProvider $beneficiaireProvider;
    protected ?string $entityName = null;
    protected ?string $accessRead = null;
    protected ?string $accessWrite = null;
    protected ?string $accessDelete = null;

    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RestManager $restManager,
        BeneficiaireProvider $beneficiaireProvider,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);

        $this->restManager = $restManager;
        $this->beneficiaireProvider = $beneficiaireProvider;
    }

    protected function editAction(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, $this->accessWrite);

            return $this->manage($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    private function manage($entity): JsonResponse
    {
        $this->provider->populate($entity, $this->apiClientManager->getCurrentOldClient());

        if (null !== $data = $this->restManager->getJsonValidationError($entity)) {
            return $this->json($data, Response::HTTP_BAD_REQUEST);
        }

        $statusCode = null === $entity->getId() ? Response::HTTP_CREATED : Response::HTTP_OK;
        $this->provider->save($entity);
        $user = $this->getUser();

        if ($entity->getBPrive() && (($user instanceof User
                    && !$user->isBeneficiaire()
                    && !$user->isAdministrateur()) || !$user instanceof User)) {
            $data = null;
        } else {
            $this->entityManager->refresh($entity);
            $data = $entity;
        }

        return $this->json($data, $statusCode);
    }

    protected function toggleAccessAction(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, $this->accessWrite);

            if ((null !== $user = $this->getUser()) && $user instanceof User && ($user->isBeneficiaire()
                    || $user->isAdministrateur())) {
                $this->provider->changePrive($entity);

                return $this->json($entity, Response::HTTP_ACCEPTED);
            }

            $this->provider->reportAbuse($entity);

            return $this->json(null, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    protected function reportAbuseAction(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, $this->accessWrite);

            $this->provider->reportAbuse($entity);

            return $this->json(null, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    protected function deleteAction(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, $this->accessDelete);

            $this->provider->delete($entity);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    protected function addAction(int $beneficiaryId): JsonResponse
    {
        try {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaryId, $this->accessWrite);
            if (null !== $this->entityName) {
                $entity = new $this->entityName($beneficiaire);

                return $this->manage($entity);
            }
            throw new \RuntimeException('Missing entity name.');
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    protected function getEntityAction(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, $this->accessRead);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
