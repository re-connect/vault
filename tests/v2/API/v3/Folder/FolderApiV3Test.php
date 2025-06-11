<?php

namespace App\Tests\v2\API\v3\Folder;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class FolderApiV3Test extends AbstractApiTest
{
    private readonly BeneficiaireRepository $beneficiaireRepository;

    protected function setUp(): void
    {
        $this->beneficiaireRepository = $this->getContainer()->get(BeneficiaireRepository::class);
        parent::setUp();
    }

    public function testGetCollection(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s/folders', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/folder',
                '@id' => sprintf('/api/v3/beneficiaries/%s/folders', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(FolderFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false])),
            ]
        );
    }

    public function testPost(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $folder = [
            'beneficiaire_id' => $beneficiary->getId(),
            'nom' => 'CONTACT',
            'dossierParentId' => $beneficiary->getRootFolders()->first()->getId(),
            'bPrive' => true,
        ];

        $this->assertEndpoint(
            'reconnect_pro',
            '/folders',
            'POST',
            201,
            [
                '@context' => '/api/contexts/folder',
                '@type' => 'folder',
                ...[
                    'beneficiaire_id' => $beneficiary->getId(),
                    'nom' => 'CONTACT',
                ],
            ],
            $folder
        );
    }
}
