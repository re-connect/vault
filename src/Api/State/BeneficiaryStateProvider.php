<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;

class BeneficiaryStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $itemProvider,
        private readonly ApiClientManager $apiClientManager
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $item = $this->itemProvider->provide($operation, $uriVariables, $context);

        if ($item instanceof Beneficiaire) {
            $client = $this->apiClientManager->getCurrentOldClient();
            if ($client) {
                $item->setDistantId($item->getExternalLinkForClient($client)?->getDistantId());
            }
        }

        return $item;
    }
}
