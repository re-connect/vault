<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\ClientFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class GetTest extends AbstractApiTest
{
    private readonly BeneficiaireRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getContainer()->get(BeneficiaireRepository::class);
        parent::setUp();
    }

    public function testGetBeneficiary(): void
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $this->repo->findByClientIdentifier($client->getRandomId())[0];
        $user = $beneficiary->getUser();
        $this->assertEndpoint(
            'reconnect_pro',
            sprintf('/beneficiaries/%s', $beneficiary->getId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'beneficiary',
                'date_naissance' => $beneficiary->getDateNaissance()->format('c'),
                'centres' => [],
                'total_file_size' => $beneficiary->getTotalFileSize(),
                'user' => [
                    '@type' => 'User',
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'telephone' => $user->getTelephone(),
                    'prenom' => $user->getPrenom(),
                    'nom' => $user->getNom(),
                    'type_user' => 'ROLE_BENEFICIAIRE',
                    'b_first_mobile_connexion' => $user->getBFirstMobileConnexion(),
                ],
            ],
        );
    }
}
