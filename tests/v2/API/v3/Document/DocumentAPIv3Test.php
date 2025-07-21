<?php

namespace App\Tests\v2\API\v3\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\API\v3\AbstractApiTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentAPIv3Test extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollectionForBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollectionForBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/beneficiaries/%s/documents', $beneficiary->getId()),
            'GET',
        );
    }

    public function canGetProvider(): \Generator
    {
        yield 'Should read when read and update scopes' => ['read_and_update'];
        yield 'Should read with Reconnect Pro client' => ['reconnect_pro'];
        yield 'Should read with Rosalie client ' => ['rosalie'];
        yield 'Should read with read only scopes' => ['read_only'];
    }

    public function canNotGetProvider(): \Generator
    {
        yield 'Should not read with create only scopes' => ['create_only'];
        yield 'Should not read with no scopes' => ['no_scopes'];
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

    /**
     * @dataProvider canUpdateProvider
     */
    public function testToggleVisibility(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $document = DocumentFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $documentId = $document->getId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/documents/%s/toggle-visibility', $documentId),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Document',
                '@id' => sprintf('/api/v3/documents/%s/toggle-visibility', $documentId),
                '@type' => 'Document',
            ],
            []
        );
        // Once item has been set to private, it should not be found
        $this->assertEndpoint(
            $clientName,
            sprintf('/documents/%s/toggle-visibility', $documentId),
            'PATCH',
            404,
            null,
            []
        );
    }

    /**
     * @dataProvider canNotUpdateProvider
     */
    public function testCanNotToggleVisibility(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);

        $document = DocumentFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $documentId = $document->getId();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/documents/%s/toggle-visibility', $documentId),
            'PATCH',
            []
        );
    }

    public function canUpdateProvider(): \Generator
    {
        yield 'Should update with read_and_update scopes' => ['read_and_update'];
        yield 'Should update with Reconnect Pro scopes' => ['reconnect_pro'];
    }

    public function canNotUpdateProvider(): \Generator
    {
        yield 'Should not update with read only scopes' => ['read_only'];
        yield 'Should not update with Rosalie scopes' => ['rosalie'];
        yield 'Should not update with no scopes' => ['no_scopes'];
        yield 'Should not update with create only scopes' => ['create_only'];
    }
}
