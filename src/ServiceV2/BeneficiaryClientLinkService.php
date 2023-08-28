<?php

namespace App\ServiceV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

class BeneficiaryClientLinkService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepository,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    public function linkBeneficiaryToClientWithName(Beneficiaire $beneficiary, string $clientName, string $externalId, string $memberExternalId = null): void
    {
        if ($client = $this->clientRepository->findOneBy(['nom' => $clientName])) {
            $this->linkBeneficiaryToClient($beneficiary, $client, $externalId, $memberExternalId);
        }
    }

    public function linkBeneficiaryToClient(Beneficiaire $beneficiary, Client $client, string $externalId, string $memberExternalId = null): void
    {
        $beneficiary->addClientExternalLink($client, $externalId, $memberExternalId);
    }

    public function unlinkBeneficiaryForCurrentClient(Beneficiaire $beneficiary): void
    {
        $this->unlinkBeneficiaryForClient($beneficiary, $this->apiClientManager->getCurrentOldClient());
    }

    public function unlinkBeneficiaryForClient(Beneficiaire $beneficiary, Client $client): void
    {
        foreach ($beneficiary->getExternalLinksForClient($client) as $link) {
            $this->em->remove($link);
        }

        $this->em->flush();
    }
}
