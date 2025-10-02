<?php

namespace App\Tests\v2\API\v3\Folder;

use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class FolderApiV3Test extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollection(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());
        $foldersCount = 0;
        foreach ($beneficiaries as $beneficiary) {
            $foldersCount += $beneficiary->getSharedRootFolders()->count();
        }

        $this->assertEndpoint(
            $clientName,
            '/folders',
            'GET',
            200,
            [
                '@context' => '/api/contexts/folder',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => $foldersCount,
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollection(string $clientName): void
    {
        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/folders',
            'GET',
        );
    }

    /**
     * @dataProvider canGetBeneficiaryProvider
     */
    public function testGetCollectionFromBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canGetBeneficiaryProvider
     */
    public function testGetCollectionTreeFromBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries/%s/folders_tree', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/folder',
                '@id' => sprintf('/api/v3/beneficiaries/%s/folders_tree', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(FolderFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false, 'dossierParent' => null])),
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollectionFromBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/beneficiaries/%s/folders', $beneficiary->getId()),
            'GET',
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
            'nom' => 'Folder with documents',
        ])->object();

        // Check that only public documents are returned
        $publicDocuments = [];
        foreach ($folder->getDocuments() as $document) {
            if (!$document->getBPrive()) {
                $publicDocuments[] = [
                    'id' => $document->getId(),
                    'b_prive' => false,
                    'nom' => $document->getNom(),
                    'beneficiaire_id' => $beneficiary->getId(),
                    'created_at' => $document->getCreatedAt()->format('c'),
                    'updated_at' => $document->getUpdatedAt()->format('c'),
                ];
            }
        }

        $this->assertEndpoint(
            $clientName,
            sprintf('/folders/%d', $folder->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/folder',
                '@type' => 'folder',
                'nom' => $folder->getNom(),
                'b_prive' => $folder->getBPrive(),
                'beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $folder->getBeneficiaire()->getId()),
                'created_at' => $folder->getCreatedAt()->format('c'),
                'updated_at' => $folder->getUpdatedAt()->format('c'),
                'documents' => $publicDocuments,
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/folders/%d', $folder->getId()),
            'GET',
        );
    }

    public function canGetProvider(): \Generator
    {
        yield 'Should read when read and update scopes' => ['read_and_update_client'];
        yield 'Should read with Reconnect Pro client' => ['reconnect_pro'];
        yield 'Should read with Rosalie client ' => ['rosalie'];
        yield 'Should read with read only scopes' => ['read_only_client'];
        yield 'Should read with read personal data scope' => ['read_personal_data_client'];
    }

    public function canGetBeneficiaryProvider(): \Generator
    {
        yield 'Should read when read and update scopes' => ['read_and_update_client'];
        yield 'Should read with Reconnect Pro client' => ['reconnect_pro'];
        yield 'Should read with Rosalie client ' => ['rosalie'];
        yield 'Should read with read only scopes' => ['read_only_client'];
    }

    public function canNotGetProvider(): \Generator
    {
        yield 'Should not read with create only scopes' => ['create_only_client'];
        yield 'Should not read with no scopes' => ['no_scopes_client'];
        yield 'Should not read with create personal data scope' => ['create_personal_data_client'];
    }

    /**
     * @dataProvider canCreateProvider
     */
    public function testPost(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $folder = [
            'beneficiaire_id' => $beneficiary->getId(),
            'nom' => 'CONTACT',
            'dossierParentId' => $beneficiary->getRootFolders()->first()->getId(),
            'bPrive' => true,
        ];

        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canNotCreateProvider
     */
    public function testCanNotPost(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $folder = [
            'beneficiaire_id' => $beneficiary->getId(),
            'nom' => 'CONTACT',
            'dossierParentId' => $beneficiary->getRootFolders()->first()->getId(),
            'bPrive' => true,
        ];

        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/folders',
            'POST',
            $folder
        );
    }

    public function canCreateProvider(): \Generator
    {
        yield 'Should create with only create scopes' => ['create_only_client'];
        yield 'Should create with update and read scopes' => ['read_and_update_client'];
        yield 'Should create with RP scopes' => ['reconnect_pro'];
        yield 'Should create with Rosalie scopes' => ['rosalie'];
        yield 'Should create with create personal data scope' => ['create_personal_data_client'];
        yield 'Should create with update personal data scope' => ['update_personal_data_client'];
    }

    public function canNotCreateProvider(): \Generator
    {
        yield 'Should not create with only read scopes' => ['read_only_client'];
        yield 'Should not create with no scopes' => ['no_scopes_client'];
        yield 'Should not create with read personal data scope' => ['read_personal_data_client'];
    }

    /**
     * @dataProvider canUpdateProvider
     */
    public function testToggleVisibility(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $folderId = $folder->getId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/folders/%s/toggle-visibility', $folderId),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/folder',
                '@id' => sprintf('/api/v3/folders/%s/toggle-visibility', $folderId),
                '@type' => 'folder',
            ],
            []
        );
        // Once item has been set to private, it should not be found
        $this->assertEndpoint(
            $clientName,
            sprintf('/folders/%s/toggle-visibility', $folderId),
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
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $folderId = $folder->getId();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/folders/%s/toggle-visibility', $folderId),
            'PATCH',
            []
        );
    }

    public function canUpdateProvider(): \Generator
    {
        yield 'Should update with read_and_update scopes' => ['read_and_update_client'];
        yield 'Should update with Reconnect Pro scopes' => ['reconnect_pro'];
        yield 'Should update with update personal data scope' => ['update_personal_data_client'];
    }

    public function canNotUpdateProvider(): \Generator
    {
        yield 'Should not update with read only scopes' => ['read_only_client'];
        yield 'Should not update with Rosalie scopes' => ['rosalie'];
        yield 'Should not update with no scopes' => ['no_scopes_client'];
        yield 'Should not update with create only scopes' => ['create_only_client'];
        yield 'Should not update with read personal data scope' => ['read_personal_data_client'];
        yield 'Should not update with create personal data scope' => ['create_personal_data_client'];
    }
}
