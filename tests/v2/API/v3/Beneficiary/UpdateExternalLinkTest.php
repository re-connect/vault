<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Repository\ClientBeneficiaireRepository;
use App\Repository\ClientRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class UpdateExternalLinkTest extends AbstractApiTest
{
    private ClientRepository $clientRepository;
    private ClientBeneficiaireRepository $clientBeneficiaireRepository;

    protected function setUp(): void
    {
        $this->clientRepository = $this->getContainer()->get(ClientRepository::class);
        $this->clientBeneficiaireRepository = $this->getContainer()->get(ClientBeneficiaireRepository::class);

        parent::setUp();
    }

    public function testShouldUpdateDistantId(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);
        $previousDistantId = $externalLinks[0]->getDistantId();

        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s', $beneficiary->getId()),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'beneficiary',
                '@id' => sprintf('/api/v3/beneficiaries/%s', $beneficiary->getId()),
            ],
            [
                'distant_id' => sprintf('%s_new', $previousDistantId),
            ]
        );

        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);
        $newDistantId = $externalLinks[0]->getDistantId();
        $this->assertNotEquals($newDistantId, $previousDistantId);
    }

    /**
     * @dataProvider canNotUpdateProvider
     */
    public function testShouldNotUpdateDistantIdWhenNotSameClient(string $clientName): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);
        $previousDistantId = $externalLinks[0]->getDistantId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries/%s', $beneficiary->getId()),
            'PATCH',
            404,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Not Found',
            ],
            [
                'distant_id' => sprintf('%s_new', $previousDistantId),
            ]
        );

        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);
        $newDistantId = $externalLinks[0]->getDistantId();
        $this->assertEquals($newDistantId, $previousDistantId);
    }

    public function canNotUpdateProvider(): \Generator
    {
        yield 'Should not udpate as read_and_update_client' => ['read_and_update_client'];
        yield 'Should not udpate as create_only_client' => ['create_only_client'];
        yield 'Should not udpate as read_only_client' => ['read_only_client'];
        yield 'Should not udpate as read_personal_data_client' => ['read_personal_data_client'];
        yield 'Should not udpate as rosalie' => ['rosalie'];
    }
}
