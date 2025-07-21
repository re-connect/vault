<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Entity\Attributes\Beneficiaire;
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

    /**
     * @dataProvider canGetProvider
     */
    public function testCanGetBeneficiary(string $clientName): void
    {
        $beneficiary = $this->getBeneficiaryForClient($clientName);
        $user = $beneficiary->getUser();
        $this->assertEndpoint(
            $clientName,
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

    /**
     * @dataProvider canNotGetProvider
     */
    public function testCanNotGetBeneficiary(string $clientName): void
    {
       $beneficiary = $this->getBeneficiaryForClient($clientName);

        $this->assertEndpointAccessIsDenied(
            $clientName,
            sprintf('/beneficiaries/%s', $beneficiary->getId()),
            'GET',
        );
    }

    public function canNotGetProvider(): \Generator
    {
        yield 'Should not get beneficiary for client with create only scopes' => ['create_only'];
        yield 'Should not get beneficiary for client with no scopes' => ['no_scopes'];
    }

    public function canGetProvider(): \Generator
    {
        yield 'Should get beneficiary for client with read only scopes' => ['read_only'];
        yield 'Should get beneficiary for client with read and update scopes' => ['read_and_update'];
        yield 'Should get beneficiary  for Reconnect Pro client' => ['reconnect_pro'];
        yield 'Should get beneficiary for Rosalie client ' => ['rosalie'];
    }
}
