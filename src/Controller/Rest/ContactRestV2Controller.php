<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Entity\Contact;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\ContactProvider;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ContactRestControllerV2.
 *
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   }, name="re_api_contact_")
 */
final class ContactRestV2Controller extends DonneePersonnelleRestController
{
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RestManager $restManager,
        BeneficiaireProvider $beneficiaireProvider,
        ContactProvider $provider,
        ApiClientManager $apiClientManager,
    ) {
        $this->entityName = Contact::class;
        $this->provider = $provider;
        parent::__construct($requestStack, $translator, $entityManager, $restManager, $beneficiaireProvider, $apiClientManager);
    }

    /**
     * @Delete("contacts/{id}",
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
     * @Get("beneficiaries/{beneficiaryId}/contacts",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
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

            $entities = $beneficiaire->getContacts($isBeneficiaire)->toArray();

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Toggle access a contact given. (access write).
     *
     * @Patch("contacts/{id}/toggle-access",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="toggle_access"
     * )
     */
    public function toggleAccess($id): JsonResponse
    {
        return $this->toggleAccessAction($id);
    }

    /**
     * Edit a contact given. (access write).
     *
     * @Rest\Put("contacts/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="edit"
     * )
     */
    public function edit($id): JsonResponse
    {
        return $this->editAction($id);
    }

    /**
     * Add a note for a beneficiary given. (access write).
     *
     * @Rest\Post("beneficiaries/{beneficiaryId}/contacts",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     },
     *     name="add"
     * )
     */
    public function add($beneficiaryId): JsonResponse
    {
        return $this->addAction($beneficiaryId);
    }

    /**
     * Get a contact given. (access read).
     *
     * @Rest\Get("contacts/{id}",
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
