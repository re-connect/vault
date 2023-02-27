<?php

namespace App\Tests\v2\API\v3\Note;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class NoteApiV3Test extends AbstractApiTest
{
    public function testGetCollection()
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $this->assertEndpoint(
            'rosalie',
            '/notes',
            'GET',
            200,
            [
                '@context' => '/api/contexts/Note',
                '@id' => '/api/v3/notes',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(NoteFactory::all()),
            ]
        );
    }

    public function testGetOne()
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

    public function testPost()
    {
        $this->markTestSkipped('Notes api ressource is currently disabled');
        $note = [
            'nom' => 'testNom',
            'contenu' => 'testContenu',
            'b_prive' => true,
            'beneficiaire' => sprintf(
                '/api/v3/beneficiaries/%d',
                BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object()->getId()
            ),
            'created_at' => (new \DateTime())->format('c'),
            'updated_at' => (new \DateTime())->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
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

    public function testPut()
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

    public function testPatch()
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

    public function testDelete()
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
