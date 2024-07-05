<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Dto\LinkBeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\ClientBeneficiaire;
use App\ManagerV2\UserManager;
use App\Repository\BeneficiaireRepository;
use App\Repository\MembreRepository;
use App\ServiceV2\Helper\RelayAssignationHelper;
use Doctrine\ORM\EntityManagerInterface;
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
        private UserManager $userManager,
        private BeneficiaireRepository $beneficiaryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $this->entityManager->clear();
        $client = $this->apiClientManager->getCurrentOldClient();

        if ($data instanceof LinkBeneficiaryDto && $operation instanceof Patch && $client) {
            $beneficiary = $this->beneficiaryRepository->find($uriVariables['id']);
            $this->createExternalLink($data, $client, $beneficiary);

            return $beneficiary;
        }

        if ($data instanceof Beneficiaire && $operation instanceof Patch && $client) {
            $this->updateExternalLink($data, $client);
        }

        if ($data instanceof BeneficiaryDto && $operation instanceof Post) {
            $data = $data->toBeneficiary();
            if (!$data->getUser()?->getPassword()) {
                $data->getUser()?->setPlainPassword($this->userManager->getRandomPassword());
            }
            $this->createBeneficiary($data, $client);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function updateExternalLink(Beneficiaire $data, Client $client): void
    {
        $externalLink = $data->getExternalLinkForClient($client);
        $externalLink?->setDistantId($data->getDistantId());
    }

    private function createExternalLink(LinkBeneficiaryDto $data, Client $client, Beneficiaire $beneficiary): void
    {
        if ($data->externalCenter) {
            $beneficiary->setDistantId($data->distantId);
            $beneficiary->getUser()?->setExternalProId($data->externalProId);
            $this->relayAssignationHelper->assignRelayFromExternalId($beneficiary->getUser(), $client, $data->externalCenter);
            $this->entityManager->flush();

            return;
        }

        $externalLink = ClientBeneficiaire::createForMember($client, $data->distantId, $data->externalProId);
        $beneficiary->addExternalLink($externalLink);
        $this->entityManager->persist($externalLink);
        $this->entityManager->flush();
    }

    private function createBeneficiary(Beneficiaire $data, ?Client $client): void
    {
        $this->relayAssignationHelper->assignRelaysFromIdsArray($data->getUser());
        if ($client) {
            $this->relayAssignationHelper->assignRelayFromExternalId($data->getUser(), $client);
        }
        try {
            $pro = $this->membreRepository->findByDistantId($data->getUser()->getExternalProId(), $client->getRandomId());
            if ($pro) {
                $data->getUser()->addCreatorUser($pro->getUser());
            }
        } catch (NonUniqueResultException) {
            $this->apiLogger->error(sprintf('Multiple pros found for distant id %s and client %s', $data->getUser()->getExternalProId(), $client->getRandomId()));
        }
    }
}
