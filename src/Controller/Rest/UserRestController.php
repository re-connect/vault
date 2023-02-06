<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Exception\JsonResponseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/api")
 */
class UserRestController extends REController
{
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    /**
     * @Route("/user", methods={"GET"})
     */
    public function getUserAction(): JsonResponse
    {
        try {
            $user = $this->getUser();

            return $this->json($user->jsonSerializeAPI(), Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
