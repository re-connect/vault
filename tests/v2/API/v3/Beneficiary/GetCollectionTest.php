<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Tests\Factory\ClientFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class GetCollectionTest extends AbstractApiTest
{
    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollection(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());

        $this->assertEndpoint(
            $clientName,
            '/beneficiaries',
            'GET',
            200,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => count($beneficiaries),
            ]
        );
    }

    /**
     * @dataProvider canGetProvider
     */
    public function testGetCollectionFromDistantId(string $clientName): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();
        $beneficiaries = $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId());

        $this->assertEndpoint(
            $clientName,
            sprintf('/beneficiaries?distant_id=%s', $beneficiaries[0]->getDistantId()),
            'GET',
            200,
            [
                '@context' => '/api/contexts/beneficiary',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => 1,
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
            '/beneficiaries',
            'GET',
        );
    }

    public function canGetProvider(): \Generator
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
        yield 'Should not read with update personal data scope' => ['update_personal_data_client'];
        yield 'Should not read with read personal data scope' => ['read_personal_data_client'];
    }
}
