<?php

namespace App\Tests\v2\API\v3\Note;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Attributes\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class NoteApiV3Test extends AbstractApiTest
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

    public function testGetOne(): void
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
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

    public function testPost(): void
    {
        $note = [
            'nom' => 'testNom',
            'contenu' => 'testContenu',
            'beneficiaire_id' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object()->getId(),
        ];

        $this->assertEndpoint(
            'reconnect_pro',
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

    public function testToggleVisibility(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
        $note = NoteFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $noteId = $note->getId();

        $this->assertEndpoint(
            'reconnect_pro',
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
            'reconnect_pro',
            sprintf('/notes/%s/toggle-visibility', $noteId),
            'PATCH',
            404,
            null,
            []
        );
    }
}
