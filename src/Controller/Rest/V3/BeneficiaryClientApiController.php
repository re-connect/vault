<?php

namespace App\Controller\Rest\V3;

use App\Api\Manager\ApiClientManager;
use App\Controller\Rest\V3\Dto\CheckBeneficiariesExistenceDto;
use App\ControllerV2\AbstractController;
use App\Repository\BeneficiaireRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/v3/beneficiaries', format: 'json')]
#[IsGranted('ROLE_OAUTH2_BENEFICIARIES_READ')]
final class BeneficiaryClientApiController extends AbstractController
{
    #[Route(path: '/exists', methods: ['POST'])]
    public function exists(
        #[MapRequestPayload] CheckBeneficiariesExistenceDto $dto,
        BeneficiaireRepository $repository,
        ApiClientManager $apiClientManager,
    ): JsonResponse {
        $client = $apiClientManager->getCurrentOldClient();
        if (!$client) {
            throw $this->createAccessDeniedException();
        }

        $distantIds = $dto->getNormalizedDistantIds();
        $existingDistantIds = $repository->findExistingDistantIds($distantIds, $client->getRandomId());

        $existence = [];
        foreach ($distantIds as $distantId) {
            $existence[$distantId] = in_array($distantId, $existingDistantIds, true);
        }

        return $this->json($existence);
    }
}
