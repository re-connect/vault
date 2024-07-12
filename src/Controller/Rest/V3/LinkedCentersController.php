<?php

namespace App\Controller\Rest\V3;

use App\Api\Manager\ApiClientManager;
use App\ControllerV2\AbstractController;
use App\Entity\Centre;
use App\Repository\CentreRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class LinkedCentersController extends AbstractController
{
    public function __invoke(
        Request $request,
        ApiClientManager $apiClientManager,
        CentreRepository $repository
    ): ?Centre {
        try {
            return $repository->findByDistantId(
                $request->attributes->getInt('id'),
                $apiClientManager->getCurrentOldClient()?->getRandomId()
            );
        } catch (NonUniqueResultException) {
            return null;
        }
    }
}
