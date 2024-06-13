<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Tests\v2\API\v3\AbstractApiTest;

class CreateTest extends AbstractApiTest
{
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
                '',
            ]
        );
    }
}
