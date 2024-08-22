<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Repository\ClientRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class AddExternalLinkTest extends AbstractApiTest
{
    private ClientRepository $clientRepository;

    protected function setUp(): void
    {
        $this->clientRepository = $this->getContainer()->get(ClientRepository::class);
        parent::setUp();
    }

    public function testSearchBeneficiary(): void
    {
        $beneficiary = BeneficiaireFactory::createOne();
        $user = $beneficiary->getUser();
        // With no Relay or external links
        $this->assertFalse($beneficiary->getBeneficiairesCentres()->first());
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $this->assertFalse($beneficiary->getExternalLinksForClient($client)->first());

        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s/add-external-link', $beneficiary->getId()),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'beneficiary',
                '@id' => sprintf('/api/v3/beneficiaries/%s/add-external-link', $beneficiary->getId()),
            ],
            [
                'distant_id' => 1200,
                'external_center' => 42,
                'external_pro_id' => 4972,
            ]
        );

        $beneficiaireCentre = $beneficiary->getBeneficiairesCentres()->first();
        $this->assertNotNull($beneficiaireCentre);
        $this->assertNotNull($beneficiary->getExternalLinksForClient($client)->first());
    }
}
