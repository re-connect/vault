<?php

namespace App\Tests\v2\API\v3\Contact;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class ContactApiV3Test extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollection(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());
        $contactsCount = 0;
        foreach ($beneficiaries as $beneficiary) {
            $contactsCount += $beneficiary->getContacts()->filter(function ($contact) {
                return !$contact->getBPrive();
            })->count();
        }
        $this->assertEndpoint(
            $clientName,
            '/contacts',
            'GET',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => $contactsCount,
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollection(string $clientName): void
    {
        $this->assertEndpoint(
            $clientName,
            '/contacts',
            'GET',
            403,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Access Denied.',
            ]
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollectionForBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries/%s/contacts', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@id' => sprintf('/api/v3/beneficiaries/%s/contacts', $beneficiary->getId()),
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count(ContactFactory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false])),
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetCollectionForBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries/%s/contacts', $beneficiary->getId()),
            'GET',
            403,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Access Denied.',
            ]
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
        ])->object();

        $this->assertEndpoint(
            $clientName,
            sprintf('/contacts/%d', $contact->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@type' => 'Contact',
                'nom' => $contact->getNom(),
                'prenom' => $contact->getPrenom(),
                'b_prive' => $contact->getBPrive(),
                'beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $contact->getBeneficiaire()->getId()),
                'created_at' => $contact->getCreatedAt()->format('c'),
                'updated_at' => $contact->getUpdatedAt()->format('c'),
            ]
        );
    }

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetOne(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
        ])->object();

        $this->assertEndpoint(
            $clientName,
            sprintf('/contacts/%d', $contact->getId()),
            'GET',
            403,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Access Denied.',
            ]
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
        $contact = [
            'beneficiaire_id' => $beneficiary->getId(),
            'nom' => 'CONTACT',
            'prenom' => 'test',
            'telephone' => '+33620746411',
            'email' => 'contact.test@mail.com',
            'commentaire' => 'Un commentaire',
        ];

        $this->assertEndpoint(
            $clientName,
            '/contacts',
            'POST',
            201,
            [
                '@context' => '/api/contexts/Contact',
                '@type' => 'Contact',
                ...$contact,
            ],
            $contact
        );
    }

    /**
     * @dataProvider canNotCreateProvider
     */
    public function testCanNotPost(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $contact = [
            'beneficiaire_id' => $beneficiary->getId(),
            'nom' => 'CONTACT',
            'prenom' => 'test',
            'telephone' => '+33620746411',
            'email' => 'contact.test@mail.com',
            'commentaire' => 'Un commentaire',
        ];

        $this->assertEndpoint(
            $clientName,
            '/contacts',
            'POST',
            403,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Access Denied.',
            ],
            $contact
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
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $contactId = $contact->getId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/contacts/%s/toggle-visibility', $contactId),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@id' => sprintf('/api/v3/contacts/%s/toggle-visibility', $contactId),
                '@type' => 'Contact',
            ],
            []
        );
        // Once item has been set to private, it should not be found
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/contacts/%s/toggle-visibility', $contactId),
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
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $contactId = $contact->getId();

        $this->assertEndpoint(
            $clientName,
            sprintf('/contacts/%s/toggle-visibility', $contactId),
            'PATCH',
            403,
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Access Denied.',
            ],
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

    public function testPatch(): void
    {
        $this->markTestSkipped('Contact api ressource is currently disabled');
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPATCH',
            'prenom' => 'testPrenomPATCH',
            'b_prive' => true,
            'created_at' => $contact->getCreatedAt()->format('c'),
            'updated_at' => $contact->getUpdatedAt()->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/contacts/%d', $contact->getId()),
            'PATCH',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@type' => 'Contact',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
    }

    public function testDelete(): void
    {
        $this->markTestSkipped('Contact api ressource is currently disabled');
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
            sprintf('/contacts/%d', $contact->getId()),
            'DELETE',
            204,
        );
    }
}
