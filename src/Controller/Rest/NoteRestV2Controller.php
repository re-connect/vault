<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Entity\Note;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\NoteProvider;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   }, name="re_api_note_")
 */
final class NoteRestV2Controller extends DonneePersonnelleRestController
{
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RestManager $restManager,
        BeneficiaireProvider $beneficiaireProvider,
        NoteProvider $provider,
        ApiClientManager $apiClientManager,
    ) {
        $this->entityName = Note::class;
        $this->provider = $provider;
        parent::__construct($requestStack, $translator, $entityManager, $restManager, $beneficiaireProvider, $apiClientManager);
    }

    /**
     * Delete a note given. (access delete).
     *
     * @Delete("notes/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="delete"
     * )
     */
    public function delete(int $id): JsonResponse
    {
        return $this->deleteAction($id);
    }

    /**
     * Get notes for a beneficiary given. (access read).
     *
     * @Get("beneficiaries/{beneficiaryId}/notes",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}",
     *     },
     *     name="list"
     * )
     */
    public function list(int $beneficiaryId): JsonResponse
    {
        try {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaryId);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;

            $entities = $beneficiaire->getNotes($isBeneficiaire)->toArray();

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Toggle access a note given. (access write).
     *
     * @Rest\Patch("notes/{id}/toggle-access",
     *     requirements={
     *          "id": "\d{1,10}",
     *     },
     *     name="toggle_access"
     * )
     */
    public function toggleAccess(int $id): JsonResponse
    {
        return $this->toggleAccessAction($id);
    }

    /**
     * Edit a note given. (access write).
     *
     * @Rest\Put("notes/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="edit"
     * )
     */
    public function edit(int $id): JsonResponse
    {
        return $this->editAction($id);
    }

    /**
     * Add a note for a beneficiary given. (access write).
     *
     * @Rest\Post("beneficiaries/{beneficiaryId}/notes",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     },
     *     name="add"
     * )
     */
    public function add(int $beneficiaryId): JsonResponse
    {
        return $this->addAction($beneficiaryId);
    }

    /**
     * Get a note given. (access read).
     *
     * @Rest\Get("notes/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="get"
     * )
     */
    public function getEntity(int $id): JsonResponse
    {
        return $this->getEntityAction($id);
    }
}
