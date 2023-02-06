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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli", name="api_note_", options={"expose"=true})
 */
final class NoteRestController extends DonneePersonnelleRestController
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
     * @Route(
     *     "/notes/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function deleteAction(int $id): JsonResponse
    {
        return parent::deleteAction($id);
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/notes",
     *     name="list",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function listAction(int $id): JsonResponse
    {
        try {
            $beneficiaire = $this->beneficiaireProvider->getEntity($id);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;

            $entities = $beneficiaire->getNotes($isBeneficiaire)->toArray();

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/notes/{id}/toggle-access",
     *     name="toggle_access",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function toggleAccessAction($id): JsonResponse
    {
        return parent::toggleAccessAction($id);
    }

    /**
     * @Route(
     *     "/notes/{id}/report-abuse",
     *     name="report_abuse",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function reportAbuseAction(int $id): JsonResponse
    {
        return parent::reportAbuseAction($id);
    }

    /**
     * @Route(
     *     "/notes/{id}",
     *     name="update",
     *     methods={"PUT"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function editAction(int $id): JsonResponse
    {
        return parent::editAction($id);
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/notes",
     *     name="add",
     *     methods={"POST"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function addAction(int $id): JsonResponse
    {
        return parent::addAction($id);
    }
}
