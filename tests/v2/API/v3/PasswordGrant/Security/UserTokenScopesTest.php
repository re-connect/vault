<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Security;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Partner scopes (ROLE_OAUTH2_*) must not be granted to user tokens, whatever the scopes requested at login.
 */
class UserTokenScopesTest extends AbstractPasswordGrantApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A client inserted without scopes (NULL) is granted any requested scope by League. League's own client manager
        // adds the "default" scope on save, but older or manually inserted clients can still have NULL scopes.
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE oauth2_client SET scopes = NULL WHERE identifier = ?',
            [ClientFactory::find(['nom' => 'applimobile'])->getRandomId()],
        );
    }

    public function provideScopedReads(): \Generator
    {
        yield 'Note' => ['notes_read', '/notes', NoteFactory::class];
        yield 'Contact' => ['contacts_read', '/contacts', ContactFactory::class];
        yield 'Event' => ['events_read', '/events', EventFactory::class];
    }

    /**
     * @dataProvider provideScopedReads
     *
     * @param class-string<NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testRequestedScopesDoNotGrantAccessToAnotherBeneficiaryData(string $scope, string $endpoint, string $factory): void
    {
        $otherBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);
        $privateItem = $factory::createOne(['beneficiaire' => $otherBeneficiary, 'bPrive' => true]);

        $client = static::createClient();
        $this->loginAsUserWithScope($client, BeneficiaryFixture::BENEFICIARY_MAIL, $scope);
        $client->request(Request::METHOD_GET, $this->generateUrl(sprintf('%s/%d', $endpoint, $privateItem->getId())));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRequestedScopesAreNotGrantedToUserTokens(): void
    {
        $client = static::createClient();
        $response = $this->loginAsUserWithScope($client, BeneficiaryFixture::BENEFICIARY_MAIL, 'notes_read documents_create beneficiaries_read');

        $this->assertSame([], array_values(array_diff(explode(' ', $this->getTokenScopes($response['access_token'])), ['default', ''])));
    }

    /** @return array<string, mixed> */
    private function loginAsUserWithScope(Client $client, string $email, string $scope): array
    {
        $apiClient = ClientFactory::find(['nom' => 'applimobile']);
        // The League token endpoint is publicly reachable and accepts any "scope" parameter
        $client->request('POST', '/api/token', ['extra' => ['parameters' => [
            'grant_type' => 'password',
            'client_id' => $apiClient->getRandomId(),
            'client_secret' => $apiClient->getSecret(),
            'username' => $email,
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
            'scope' => $scope,
        ]]]);
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->accessToken = $response['access_token'];

        return $response;
    }

    private function getTokenScopes(string $jwt): string
    {
        $payload = json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);

        return implode(' ', $payload['scopes'] ?? []);
    }
}
