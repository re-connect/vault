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

    /**
     * @dataProvider canCreateProvider
     */
    public function testCreateBeneficiary(string $clientName, ?string $externalCenterId = null): void
    {
        $beneficiary = [
            'last_name' => 'test',
            'first_name' => 'api',
            'birth_date' => '2023-02-13T13:44:28.762Z',
            'email' => 'api@test.com',
            'phone' => '1234567890',
            'distant_id' => '1200',
            'external_pro_id' => '4972',
        ];

        if ($externalCenterId) {
            $beneficiary['external_center'] = $externalCenterId;
        }

        $this->assertEndpoint(
            $clientName,
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
            $beneficiary
        );

        $benef = $this->repo->findByUsername('api.test.13/02/2023');
        $this->assertNotNull($benef);
        $this->assertNotEmpty($benef->getExternalLinks());
        $externalLink = $benef->getExternalLinks()->first();
        $this->assertEquals($clientName, $externalLink->getClient()->getNom());
        if ($externalCenterId) {
            $beneficiaireCentre = $benef->getBeneficiairesCentres()->first();
            $this->assertEquals($beneficiaireCentre, $externalLink->getBeneficiaireCentre());
            $this->assertTrue($beneficiaireCentre->getBValid());
        }
        $this->assertEquals(1200, $benef->getExternalLinks()->first()->getDistantId());
    }

    /**
     * @dataProvider canNotCreateProvider
     */
    public function testCanNotCreateBeneficiary(string $clientName): void
    {
        $this->assertEndpointAccessIsDenied(
            $clientName,
            '/beneficiaries',
            'POST',
            [
                'last_name' => 'test',
                'first_name' => 'api',
                'birth_date' => '2023-02-13T13:44:28.762Z',
                'email' => 'api@test.com',
                'phone' => '1234567890',
                'distant_id' => '1200',
            ]
        );
    }

    public function canNotCreateProvider(): \Generator
    {
        yield 'Should not create beneficiary for client with readonly scopes' => ['read_only'];
        yield 'Should not create beneficiary for client with no scopes' => ['no_scopes'];
    }

    public function canCreateProvider(): \Generator
    {
        yield 'Should create beneficiary for client with update scopes' => ['read_and_update'];
        yield 'Should create beneficiary for client with create scopes' => ['create_only'];
        yield 'Should create beneficiary with external center for Reconnect Pro client' => ['reconnect_pro', '42'];
        yield 'Should create beneficiary for Rosalie client ' => ['rosalie'];
    }
}
