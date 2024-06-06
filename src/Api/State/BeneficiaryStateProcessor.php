<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Repository\MembreRepository;
use App\ServiceV2\Helper\RelayAssignationHelper;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;

readonly class BeneficiaryStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiClientManager $apiClientManager,
        private ProcessorInterface $persistProcessor,
        private RelayAssignationHelper $relayAssignationHelper,
        private MembreRepository $membreRepository,
        private LoggerInterface $apiLogger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        if ($data instanceof Beneficiaire && $operation instanceof Patch && $client) {
            $this->updateExternalLink($data, $client);
        } elseif ($data instanceof BeneficiaryDto && $operation instanceof Post) {
            $this->createBeneficiary($data, $client);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function updateExternalLink(Beneficiaire $data, Client $client): void
    {
        $externalLink = $data->getExternalLinkForClient($client);
        $externalLink?->setDistantId($data->getDistantId());
    }

    private function createBeneficiary(BeneficiaryDto $data, ?Client $client): void
    {
        $data = $data->toBeneficiary();
        $this->relayAssignationHelper->assignRelaysFromIdsArray($data->getUser());
        if ($client) {
            $this->relayAssignationHelper->assignRelayFromExternalId($data->getUser(), $client);
        }
        try {
            $pro = $this->membreRepository->findByDistantId($data->getUser()->getExternalProId(), $client->getRandomId());
            if ($pro) {
                $data->getUser()->addCreatorUser($pro->getUser());
            }
        } catch (NonUniqueResultException $e) {
            $this->apiLogger->error(sprintf('Multiple pros found for distant id %s and client %s', $data->getUser()->getExternalProId(), $client->getRandomId()));
        }
    }
}
