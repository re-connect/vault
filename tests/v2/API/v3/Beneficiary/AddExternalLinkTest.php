<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
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

    public function testAddExternalLink(): void
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

    public function testShouldNotAddExternalLink(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);
        $client = $this->clientRepository->findOneBy(['nom' => 'rosalie']);
        $this->assertNotNull($beneficiary->getExternalLinksForClient($client)->first());

        $this->assertEndpoint(
            'rosalie',
            sprintf('/beneficiaries/%s/add-external-link', $beneficiary->getId()),
            'PATCH',
            422,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
            ],
            [
                'distant_id' => 1200,
                'external_center' => 42,
                'external_pro_id' => 4972,
            ]
        );
    }

    public function testShouldAddSecondExternalLink(): void
    {
        // This sould only work for Reconnect Pro Client
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK);
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $this->assertNotNull($beneficiary->getExternalLinksForClient($client)->first());

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
                'distant_id' => 1250,
                'external_center' => 42,
                'external_pro_id' => 4972,
            ]
        );

        $beneficiaireCentre = $beneficiary->getBeneficiairesCentres()->first();
        $this->assertNotNull($beneficiaireCentre);
        $this->assertNotNull($beneficiary->getExternalLinksForClient($client)->first());
    }
}
