<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Repository\ClientBeneficiaireRepository;
use App\Repository\ClientRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\v3\AbstractApiTest;
use Doctrine\ORM\EntityManagerInterface;

class AddExternalLinkTest extends AbstractApiTest
{
    private ClientRepository $clientRepository;
    private ClientBeneficiaireRepository $clientBeneficiaireRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->clientRepository = $this->getContainer()->get(ClientRepository::class);
        $this->clientBeneficiaireRepository = $this->getContainer()->get(ClientBeneficiaireRepository::class);

        parent::setUp();
    }

    public function testAddExternalLink(): void
    {
        $beneficiary = BeneficiaireFactory::createOne();
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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);

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
                'external_center' => 43,
                'external_pro_id' => 4972,
            ]
        );

        $beneficiaireCentres = $beneficiary->getBeneficiairesCentres();
        $this->assertCount(2, $beneficiaireCentres);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(2, $externalLinks);
    }

    public function testShouldNotAddSecondExternalLink(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $client = $this->clientRepository->findOneBy(['nom' => 'reconnect_pro']);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);

        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s/add-external-link', $beneficiary->getId()),
            'PATCH',
            422,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:description' => 'This beneficiary already has a link for this client and center.',
            ],
            [
                'distant_id' => 1250,
                'external_center' => 42,
                'external_pro_id' => 4972,
            ]
        );

        $beneficiaireCentres = $beneficiary->getBeneficiairesCentres();
        $this->assertCount(1, $beneficiaireCentres);
        $externalLinks = $this->clientBeneficiaireRepository->findBy(['entity' => $beneficiary, 'client' => $client]);
        $this->assertCount(1, $externalLinks);
    }
}
