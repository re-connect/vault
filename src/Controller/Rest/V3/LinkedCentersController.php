<?php

namespace App\Controller\Rest\V3;

use App\Api\Manager\ApiClientManager;
use App\ControllerV2\AbstractController;
use App\Repository\CentreRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class LinkedCentersController extends AbstractController
{
    public function __invoke(
        Request $request,
        ApiClientManager $apiClientManager,
        CentreRepository $repository,
    ): Response {
        try {
            return $this->json($repository->findByDistantId(
                $request->attributes->getInt('id'),
                $apiClientManager->getCurrentOldClient()?->getRandomId()
            ), Response::HTTP_OK, [], ['groups' => ['v3:center:read']]);
        } catch (NonUniqueResultException) {
            throw $this->createNotFoundException();
        }
    }
}
