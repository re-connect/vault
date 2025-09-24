<?php

namespace App\Tests\v2\API\v3\Event;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class EventApiV3Test extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollection(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());
        $eventCounts = 0;
        foreach ($beneficiaries as $beneficiary) {
            $eventCounts += $beneficiary->getEvenements()->filter(function ($event) {
                return !$event->getBPrive();
            })->count();
        }

        $this->assertEndpoint(
            $clientName,
            '/events',
            'GET',
            200,
            [
                '@context' => '/api/contexts/Event',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => $eventCounts,
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
            '/events',
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

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollectionFromBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/beneficiaries/%s/events', $beneficiary->getId()),
            'GET',
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $event = EventFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();

        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $event = EventFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/events/%d', $event->getId()),
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
        $event = [
            'beneficiaire_id' => $this->getBeneficiaryForClient($clientName)->getId(),
            'nom' => 'testNom',
            'date' => (new \DateTime('tomorrow'))->format('c'),
            'lieu' => 'testLieu',
            'b_prive' => false,
        ];

        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canNotCreateProvider
     */
    public function testCanNotPost(string $clientName): void
    {
        $event = [
            'beneficiaire_id' => $this->getBeneficiaryForClient($clientName)->getId(),
            'nom' => 'testNom',
            'date' => (new \DateTime('tomorrow'))->format('c'),
            'lieu' => 'testLieu',
            'b_prive' => false,
        ];

        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/events',
            'POST',
            $event
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
        $event = EventFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $eventId = $event->getId();

        $this->assertEndpoint(
            $clientName,
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
            $clientName,
            sprintf('/events/%s/toggle-visibility', $eventId),
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
        $event = EventFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $eventId = $event->getId();

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/events/%s/toggle-visibility', $eventId),
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
}
