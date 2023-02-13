<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use Doctrine\ORM\EntityManagerInterface;

class BeneficiaryStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ApiClientManager $apiClientManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof Beneficiaire) {
            return;
        }
        if (!$apiClient = $this->apiClientManager->getCurrentOldClient()) {
            return;
        }

        if ($externalLink = $data->getExternalLinkForClient($apiClient)) {
            $externalLink->setDistantId($data->getDistantId());
            $this->em->flush();
        }
    }
}
