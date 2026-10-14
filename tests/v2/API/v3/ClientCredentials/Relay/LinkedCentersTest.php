<?php

namespace App\Tests\v2\API\v3\ClientCredentials\Relay;

use App\DataFixtures\v2\RelayFixture;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\API\AbstractClientCredentialsApiTestCase;

class LinkedCentersTest extends AbstractClientCredentialsApiTestCase
{
    public function testGetCenterFromItsDistantId(): void
    {
        $relay = RelayFactory::find(['nom' => RelayFixture::DEFAULT_BENEFICIARY_RELAY]);

        $this->assertEndpoint('reconnect_pro', '/linked-centers/42', 'GET', 200, ['id' => $relay->getId()]);
    }

    public function provideClientsWithoutCentersScope(): \Generator
    {
        yield 'No scopes' => ['no_scopes_client'];
        yield 'Read only scopes' => ['read_only_client'];
    }

    /** @dataProvider provideClientsWithoutCentersScope */
    public function testCanNotGetCenterWithoutCentersScope(string $clientName): void
    {
        $this->assertEndpoint($clientName, '/linked-centers/42', 'GET', 403);
    }
}
