<?php

namespace App\Tests\v2\API\v3\Note;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class NoteApiV3Test extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollection(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());
        $notesCount = 0;
        foreach ($beneficiaries as $beneficiary) {
            $notesCount += $beneficiary->getNotes()->filter(function ($note) {
                return !$note->getBPrive();
            })->count();
        }

        $this->assertEndpoint(
            $clientName,
            '/notes',
            'GET',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => $notesCount,
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testNotGetCollection(string $clientName): void
    {
        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/notes',
            'GET',
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollectionFromBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries/%s/notes', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@id' => sprintf('/api/v3/beneficiaries/%s/notes', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(NoteFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false])),
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
            sprintf('/beneficiaries/%s/notes', $beneficiary->getId()),
            'GET',
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();

        $this->assertEndpoint(
            $clientName,
            sprintf('/notes/%d', $note->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@type' => 'Note',
                'nom' => $note->getNom(),
                'contenu' => $note->getContenu(),
                'b_prive' => $note->getBPrive(),
                'beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $note->getBeneficiaire()->getId()),
                'created_at' => $note->getCreatedAt()->format('c'),
                'updated_at' => $note->getUpdatedAt()->format('c'),
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/notes/%d', $note->getId()),
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

    /**
     * @dataProvider canCreateProvider
     */
    public function testPost(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $note = [
            'nom' => 'testNom',
            'contenu' => 'testContenu',
            'beneficiaire_id' => $beneficiary->getId(),
        ];

        $this->assertEndpoint(
            $clientName,
            '/notes',
            'POST',
            201,
            [
                '@context' => '/api/contexts/Note',
                '@type' => 'Note',
                ...$note,
            ],
            $note
        );
    }

    /**
     * @dataProvider canNotCreateProvider
     */
    public function testCanNotPost(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $note = [
            'nom' => 'testNom',
            'contenu' => 'testContenu',
            'beneficiaire_id' => $beneficiary->getId(),
        ];

        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/notes',
            'POST',
            $note
        );
    }

    public function canCreateProvider(): \Generator
    {
        yield 'Should create with only create scopes' => ['create_only'];
        yield 'Should create with RP scopes' => ['reconnect_pro'];
        yield 'Should create with Rosalie scopes' => ['rosalie'];
    }

    public function canNotCreateProvider(): \Generator
    {
        yield 'Should not create with only read scopes' => ['read_only'];
        yield 'Should not create with no scopes' => ['no_scopes'];
    }

    /**
     * @dataProvider canUpdateProvider
     */
    public function testToggleVisibility(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $noteId = $note->getId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/notes/%s/toggle-visibility', $noteId),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@id' => sprintf('/api/v3/notes/%s/toggle-visibility', $noteId),
                '@type' => 'Note',
            ],
            []
        );
        // Once item has been set to private, it should not be found
        $this->assertEndpoint(
            $clientName,
            sprintf('/notes/%s/toggle-visibility', $noteId),
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
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $noteId = $note->getId();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/notes/%s/toggle-visibility', $noteId),
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

    public function testPut(): void
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPUT',
            'contenu' => 'testContenuPUT',
            'b_prive' => true,
            'created_at' => (new \DateTime())->format('c'),
            'updated_at' => (new \DateTime())->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/notes/%d', $note->getId()),
            'PUT',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@type' => 'Note',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
    }

    public function testPatch(): void
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPATCH',
            'contenu' => 'testContenuPATCH',
            'b_prive' => true,
            'created_at' => (new \DateTime())->format('c'),
            'updated_at' => (new \DateTime())->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/notes/%d', $note->getId()),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@type' => 'Note',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
    }

    public function testDelete(): void
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
            sprintf('/notes/%d', $note->getId()),
            'DELETE',
            204,
        );
    }
}
