<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class SearchTest extends AbstractApiTest
{
    public function testSearchBeneficiary(): void
    {
        $beneficiary = BeneficiaireFactory::first();
        $user = $beneficiary->getUser();
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/users?username=%s', $user->getUsername()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/User',
                '@type' => 'hydra:Collection',
                '@id' => '/api/v3/users',
                'hydra:totalItems' => 1,
                'hydra:member' => [
                    [
                        '@type' => 'beneficiary',
                        'id' => $user->getSubjectBeneficiaire()->getId(),
                        'user' => [
                            '@type' => 'User',
                            'username' => $user->getUsername(),
                            'email' => $user->getEmail(),
                            'id' => $user->getId(),
                            'created_at' => $user->getCreatedAt()->format('c'),
                            'updated_at' => $user->getUpdatedAt()->format('c'),
                            'prenom' => $user->getPrenom(),
                            'nom' => $user->getNom(),
                            'telephone' => $user->getTelephone(),
                            'type_user' => 'ROLE_BENEFICIAIRE',
                            'b_first_mobile_connexion' => $user->getBFirstMobileConnexion(),
                        ],
                    ],
                ],
            ],
        );
    }
}
