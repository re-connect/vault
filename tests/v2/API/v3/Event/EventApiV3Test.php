<?php

namespace App\Tests\v2\API\v3\Event;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Attributes\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class EventApiV3Test extends AbstractApiTest
{
    public function testGetCollection(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s/events', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@id' => sprintf('/api/v3/beneficiaries/%s/events', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(EventFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false])),
            ]
        );
    }

    public function testGetOne(): void
    {
        $this->markTestSkipped('Event api ressource is currently disabled');
        $event = EventFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
            sprintf('/events/%d', $event->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@type' => 'Event',
                'nom' => $event->getNom(),
                'date' => $event->getDate()->format('c'),
                'b_prive' => $event->getBPrive(),
                'beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $event->getBeneficiaire()->getId()),
                'created_at' => $event->getCreatedAt()->format('c'),
                'updated_at' => $event->getUpdatedAt()->format('c'),
            ]
        );
    }

    public function testPost(): void
    {
        $event = [
            'beneficiaire_id' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object()->getId(),
            'nom' => 'testNom',
            'date' => (new \DateTime('tomorrow'))->format('c'),
            'lieu' => 'testLieu',
            'b_prive' => false,
        ];

        $this->assertEndpoint(
            'reconnect_pro',
            '/events',
            'POST',
            201,
            [
                '@context' => '/api/contexts/Event',
                '@type' => 'Event',
                ...$event,
            ],
            $event
        );
    }

    public function testPut(): void
    {
        $this->markTestSkipped('Event api ressource is currently disabled');
        $note = EventFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPUT',
            'date' => (new \DateTime('tomorrow'))->format('c'),
            'lieu' => 'testLieuPut',
            'b_prive' => true,
            'created_at' => (new \DateTime())->format('c'),
            'updated_at' => (new \DateTime())->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/events/%d', $note->getId()),
            'PUT',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@type' => 'Event',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
    }

    public function testPatch(): void
    {
        $this->markTestSkipped('Event api ressource is currently disabled');
        $event = EventFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPATCH',
            'date' => (new \DateTime('tomorrow'))->format('c'),
            'lieu' => 'testLieuPatch',
            'b_prive' => true,
            'created_at' => $event->getCreatedAt()->format('c'),
            'updated_at' => $event->getCreatedAt()->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/events/%d', $event->getId()),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@type' => 'Event',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
    }

    public function testDelete(): void
    {
        $this->markTestSkipped('Event api ressource is currently disabled');
        $event = EventFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
            sprintf('/events/%d', $event->getId()),
            'DELETE',
            204,
        );
    }

    public function testToggleVisibility(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
        $event = EventFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $eventId = $event->getId();

        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/events/%s/toggle-visibility', $eventId),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@id' => sprintf('/api/v3/events/%s/toggle-visibility', $eventId),
                '@type' => 'Event',
            ],
            []
        );
        // Once item has been set to private, it should not be found
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/events/%s/toggle-visibility', $eventId),
            'PATCH',
            404,
            null,
            []
        );
    }
}
