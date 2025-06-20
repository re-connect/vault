<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Entity\Evenement;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\EvenementProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['old' => '/api/', 'new' => '/api/v2/'], name: 're_api_event_')]
final class EvenementRestV2Controller extends DonneePersonnelleRestController
{
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RestManager $restManager,
        BeneficiaireProvider $beneficiaireProvider,
        EvenementProvider $provider,
        ApiClientManager $apiClientManager,
    ) {
        $this->entityName = Evenement::class;
        $this->provider = $provider;
        parent::__construct($requestStack, $translator, $entityManager, $restManager, $beneficiaireProvider, $apiClientManager);
    }

    #[Route(path: 'events/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->deleteAction($id);
    }

    #[Route(path: 'beneficiaries/{beneficiaryId}/events', name: 'list', requirements: ['beneficiaryId' => '\d+'], methods: ['GET'])]
    public function list(int $beneficiaryId): JsonResponse
    {
        try {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaryId);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;
            $entities = $beneficiaire->getEvenements($isBeneficiaire, false)->toArray();

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'events/{id}/toggle-access', name: 'toggle_access', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function toggleAccess(int $id): JsonResponse
    {
        return $this->toggleAccessAction($id);
    }

    #[Route(path: 'events/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function edit(int $id): JsonResponse
    {
        return $this->editAction($id);
    }

    #[Route(path: 'beneficiaries/{beneficiaryId}/events', name: 'add', requirements: ['beneficiaryId' => '\d+'], methods: ['POST'])]
    public function add(int $beneficiaryId): JsonResponse
    {
        return $this->addAction($beneficiaryId);
    }

    #[Route(path: 'events/{id}', name: 'get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getEntity(int $id): JsonResponse
    {
        return $this->getEntityAction($id);
    }
}
