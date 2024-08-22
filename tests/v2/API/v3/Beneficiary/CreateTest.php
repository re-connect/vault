<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Repository\BeneficiaireRepository;
use App\Tests\v2\API\v3\AbstractApiTest;

class CreateTest extends AbstractApiTest
{
    private readonly BeneficiaireRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getContainer()->get(BeneficiaireRepository::class);
        parent::setUp();
    }

    public function testCreateBeneficiary(): void
    {
        $this->assertEndpoint(
            'reconnect_pro',
            '/beneficiaries',
            'POST',
            201,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'beneficiary',
                'date_naissance' => '2023-02-13T00:00:00+01:00',
                'centres' => [],
                'total_file_size' => 0,
                'distant_id' => '1200',
                'user' => [
                    '@type' => 'User',
                    'username' => 'api.test.13/02/2023',
                    'email' => 'api@test.com',
                    'telephone' => '1234567890',
                    'prenom' => 'api',
                    'nom' => 'test',
                    'type_user' => 'ROLE_BENEFICIAIRE',
                    'b_first_mobile_connexion' => false,
                ],
            ],
            [
                'last_name' => 'test',
                'first_name' => 'api',
                'birth_date' => '2023-02-13T13:44:28.762Z',
                'email' => 'api@test.com',
                'phone' => '1234567890',
                'distant_id' => '1200',
                'external_center' => '42',
                'external_pro_id' => '4972',
            ]
        );
        $benef = $this->repo->findByUsername('api.test.13/02/2023');
        $this->assertNotNull($benef);
        $this->assertNotEmpty($benef->getExternalLinks());
        $externalLink = $benef->getExternalLinks()->first();
        $beneficiaireCentre = $benef->getBeneficiairesCentres()->first();
        $this->assertEquals('reconnect_pro', $externalLink->getClient()->getNom());
        $this->assertEquals($beneficiaireCentre, $externalLink->getBeneficiaireCentre());
        $this->assertEquals(1200, $benef->getExternalLinks()->first()->getDistantId());
        $this->assertTrue($beneficiaireCentre->getBValid());
    }
}
