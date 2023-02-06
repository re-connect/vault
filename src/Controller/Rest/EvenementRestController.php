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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli", name="api_evenement_", options={"expose"=true})
 */
final class EvenementRestController extends DonneePersonnelleRestController
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

    /**
     * @Route(
     *     "/events/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function deleteAction($id): JsonResponse
    {
        return parent::deleteAction($id);
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/events",
     *     name="list",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function listAction($id): JsonResponse
    {
        try {
            $beneficiaire = $this->beneficiaireProvider->getEntity($id);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;
            $entities = $beneficiaire->getEvenements($isBeneficiaire, false, false)->toArray();

            return $this->json($entities, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/events/{id}/toggle-access",
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
     *     "/events/{id}/report-abuse",
     *     name="report_abuse",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function reportAbuseAction($id): JsonResponse
    {
        return parent::reportAbuseAction($id);
    }

    /**
     * @Route(
     *     "/events/{id}",
     *     name="edit",
     *     methods={"PUT"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function editAction($id): JsonResponse
    {
        return parent::editAction($id);
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/events",
     *     name="add",
     *     methods={"POST"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function addAction($id): JsonResponse
    {
        return parent::addAction($id);
    }
}
