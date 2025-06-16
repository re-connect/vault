<?php

namespace App\Tests\v2\API\v3\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\API\v3\AbstractApiTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentAPIv3Test extends AbstractApiTest
{
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
            sprintf('/beneficiaries/%s/documents', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Document',
                '@id' => sprintf('/api/v3/beneficiaries/%s/documents', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(DocumentFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false])),
            ]
        );
    }

    public function testPost(): void
    {
        $this->markTestSkipped();
        // Create a temporary file to simulate an uploaded document
        $filePath = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($filePath, 'Test content');
        $uploadedFile = new UploadedFile(
            $filePath,
            'test.txt',
            'text/plain',
            null,
            true
        );
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();

        $this->loginAsClient('reconnect_pro');
        $options['headers'] = ['Content-Type' => 'application/json'];
        $options['body'] = json_encode([
            'beneficiary_id' => $beneficiary->getId(),
            'folder_id' => $beneficiary->getRootFolders()->first()->getId(),
        ]);

        $options['extra']['files'] = [
            'file' => $uploadedFile,
        ];

        $this->client->request(
            'POST',
            $this->generateUrl('/documents'),
            $options
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Document',
            '@type' => 'Document',
            ...[
                'beneficiaire_id' => $beneficiary->getId(),
            ],
        ]);

        // Clean up the temporary file
        unlink($filePath);
    }
}
