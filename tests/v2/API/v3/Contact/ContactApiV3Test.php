<?php

namespace App\Tests\v2\API\v3\Contact;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class ContactApiV3Test extends AbstractApiTest
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

    public function testGetOne(): void
    {
        $this->markTestSkipped('Contact api ressource is currently disabled');
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $this->assertEndpoint(
            'rosalie',
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

    public function testPost(): void
    {
        $contact = [
            'beneficiaire_id' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object()->getId(),
            'nom' => 'CONTACT',
            'prenom' => 'test',
            'telephone' => '+33620746411',
            'email' => 'contact.test@mail.com',
            'commentaire' => 'Un commentaire',
        ];

        $this->assertEndpoint(
            'reconnect_pro',
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

    public function testPut(): void
    {
        $this->markTestSkipped('Contact api ressource is currently disabled');
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
        ])->object();

        $updatedProperties = [
            'nom' => 'testNomPUT',
            'prenom' => 'testPrenomPUT',
            'b_prive' => true,
            'created_at' => (new \DateTime())->format('c'),
            'updated_at' => (new \DateTime())->format('c'),
        ];

        $this->assertEndpoint(
            'rosalie',
            sprintf('/contacts/%d', $contact->getId()),
            'PUT',
            200,
            [
                '@context' => '/api/contexts/Contact',
                '@type' => 'Contact',
                ...$updatedProperties,
            ],
            $updatedProperties
        );
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

    public function testToggleVisibility(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
        $contact = ContactFactory::findOrCreate([
            'beneficiaire' => $beneficiary,
            'bPrive' => false,
        ])->object();
        $contactId = $contact->getId();

        $this->assertEndpoint(
            'reconnect_pro',
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
}
