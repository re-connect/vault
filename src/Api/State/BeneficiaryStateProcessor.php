<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;

class BeneficiaryStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ApiClientManager $apiClientManager,
        private readonly ProcessorInterface $persistProcessor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Beneficiaire && $operation instanceof Patch) {
            $externalLink = $data->getExternalLinkForClient($this->apiClientManager->getCurrentOldClient());
            $externalLink?->setDistantId($data->getDistantId());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
