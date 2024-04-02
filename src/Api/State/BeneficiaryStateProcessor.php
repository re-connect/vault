<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\ServiceV2\Helper\RelayAssignationHelper;

readonly class BeneficiaryStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiClientManager $apiClientManager,
        private ProcessorInterface $persistProcessor,
        private RelayAssignationHelper $relayAssignationHelper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Beneficiaire && $operation instanceof Patch) {
            $externalLink = $data->getExternalLinkForClient($this->apiClientManager->getCurrentOldClient());
            $externalLink?->setDistantId($data->getDistantId());
        } elseif ($data instanceof BeneficiaryDto && $operation instanceof Post) {
            $data = $data->toBeneficiary();
            $this->relayAssignationHelper->assignRelaysFromIdsArray($data->getUser());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
