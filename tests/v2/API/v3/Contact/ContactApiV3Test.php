<?php

namespace App\Tests\v2\API\v3\Contact;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class ContactApiV3Test extends AbstractApiTest
{
    public function testGetCollection()
    {
        $this->assertEndpoint(
            'rosalie',
            '/contacts',
            'GET',
            200,
            [
            '@context' => '/api/contexts/Contact',
            '@id' => '/api/v3/contacts',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => count(ContactFactory::all()),
            ]
        );
    }

    public function testGetOne()
    {
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

    public function testPost()
    {
        $contact = [
            'nom' => 'testNom',
            'prenom' => 'testPrenom',
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

    public function testPut()
    {
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

    public function testPatch()
    {
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

    public function testDelete()
    {
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
