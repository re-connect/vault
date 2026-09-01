<?php

namespace App\Tests\v2\API\v3\Beneficiary;

use App\Controller\Rest\V3\Dto\CheckBeneficiariesExistenceDto;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\v3\AbstractApiTest;

class CheckExistenceTest extends AbstractApiTest
{
    private const string ENDPOINT = '/beneficiaries/exists';
    private const string UNKNOWN_DISTANT_ID = 'unknown-distant-id';

    /**
     * @dataProvider clientWithReadScopeProvider
     */
    public function testChecksExistenceOfItsOwnLinks(string $clientName): void
    {
        $distantId = (string) BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->getId();

        $this->assertEndpoint(
            $clientName,
            self::ENDPOINT,
            'POST',
            200,
            [
                $distantId => true,
                self::UNKNOWN_DISTANT_ID => false,
            ],
            ['distant_ids' => [$distantId, self::UNKNOWN_DISTANT_ID]],
        );
    }

    public function clientWithReadScopeProvider(): \Generator
    {
        yield 'Should check existence for client with read scopes' => ['read_only_client'];
        yield 'Should check existence for client with read and update scopes' => ['read_and_update_client'];
        yield 'Should check existence for Rosalie client' => ['rosalie'];
    }

    public function testChecksExistenceForReconnectPro(): void
    {
        $distantId = (string) BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->getId();

        $this->assertEndpoint(
            'reconnect_pro',
            self::ENDPOINT,
            'POST',
            200,
            [
                $distantId => true,
                self::UNKNOWN_DISTANT_ID => false,
            ],
            ['distant_ids' => [$distantId, self::UNKNOWN_DISTANT_ID]],
        );
    }

    public function testDoesNotLeakLinksOfOtherClients(): void
    {
        $otherClientDistantId = (string) BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->getId();
        $reconnectProDistantId = (string) BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->getId();

        $this->assertEndpoint(
            'reconnect_pro',
            self::ENDPOINT,
            'POST',
            200,
            [$otherClientDistantId => false],
            ['distant_ids' => [$otherClientDistantId]],
        );

        $this->assertEndpoint(
            'read_only_client',
            self::ENDPOINT,
            'POST',
            200,
            [$reconnectProDistantId => false],
            ['distant_ids' => [$reconnectProDistantId]],
        );
    }

    public function testAcceptsIntegerDistantIdsAndDeduplicatesThem(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);

        $existence = $this->requestExistence('read_only_client', [
            'distant_ids' => [$beneficiary->getId(), (string) $beneficiary->getId(), self::UNKNOWN_DISTANT_ID],
        ]);

        $this->assertSame([
            (string) $beneficiary->getId() => true,
            self::UNKNOWN_DISTANT_ID => false,
        ], $existence);
    }

    /**
     * @dataProvider clientWithoutReadScopeProvider
     */
    public function testAccessIsDeniedWithoutBeneficiariesReadScope(string $clientName): void
    {
        $this->assertEndpoint(
            $clientName,
            self::ENDPOINT,
            'POST',
            403,
            null,
            ['distant_ids' => [self::UNKNOWN_DISTANT_ID]],
        );
    }

    public function clientWithoutReadScopeProvider(): \Generator
    {
        yield 'Should deny access to client with no scopes' => ['no_scopes_client'];
        yield 'Should deny access to client with only create scopes' => ['create_only_client'];
        yield 'Should deny access to client with only personal data read scope' => ['read_personal_data_client'];
        yield 'Should deny access to client with only personal data update scope' => ['update_personal_data_client'];
    }

    /**
     * @dataProvider invalidPayloadProvider
     */
    public function testRejectsInvalidPayload(mixed $body): void
    {
        $this->assertEndpoint('read_only_client', self::ENDPOINT, 'POST', 422, null, $body);
    }

    public function invalidPayloadProvider(): \Generator
    {
        yield 'Should reject missing distant ids' => [[]];
        yield 'Should reject empty distant ids' => [['distant_ids' => []]];
        yield 'Should reject blank distant id' => [['distant_ids' => ['']]];
        yield 'Should reject too many distant ids' => [['distant_ids' => range(1, CheckBeneficiariesExistenceDto::MAX_IDS + 1)]];
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, bool>
     */
    private function requestExistence(string $clientName, array $body): array
    {
        $client = static::createClient();
        $this->loginAsClient($client, $clientName);

        $response = $client->request('POST', $this->generateUrl(self::ENDPOINT), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body),
        ]);

        $this->assertResponseStatusCodeSame(200);

        return json_decode($response->getContent(), true);
    }
}
