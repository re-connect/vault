<?php

namespace App\ServiceV2;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Repository\ClientRepository;

class BeneficiaryClientLinkService
{
    public function __construct(private readonly ClientRepository $clientRepository)
    {
    }

    public function linkBeneficiaryToClient(Beneficiaire $beneficiary, string $clientName, string $externalId, ?Centre $relay = null, ?string $memberExternalId = null): void
    {
        $client = $this->clientRepository->findOneBy(['nom' => $clientName]);

        $beneficiary->addClientExternalLink($client, $externalId, $memberExternalId);

        if ($relay) {
            $beneficiary->addBeneficiaryRelayForRelay($relay)->addCreatorRelay($relay);
        }
    }
}
