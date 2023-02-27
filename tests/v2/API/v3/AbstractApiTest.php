<?php

namespace App\Tests\v2\API\v3;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Factory\ClientFactory;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractApiTest extends ApiTestCase
{
    use Factories;

    protected Client $client;
    protected ?string $accessToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * @param array<string, string> $expectedJson
     */
    public function assertEndpoint(string $clientName, string $endpoint, string $method, int $expectedStatusCode, array $expectedJson = null, mixed $body = null): void
    {
        $this->loginAsClient($clientName);
        $options = ['json' => $body];
        if (Request::METHOD_PATCH === $method) {
            $options['headers'] = ['Content-Type' => 'application/merge-patch+json'];
        }
        $this->client->request($method, $this->generateUrl($endpoint), $options);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedJson) {
            $this->assertJsonContains($expectedJson);
        }
    }

    public function loginAsClient(string $clientName, string $grantType = 'client_credentials'): void
    {
        /** @var \League\Bundle\OAuth2ServerBundle\Model\Client $client */
        $client = ClientFactory::find(['name' => $clientName])->object();

        $response = $this->client->request('POST', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
            'scope' => 'centers beneficiaries documents notes contacts pros events',
        ]]);

        $content = json_decode($response->getContent(), true);
        $this->accessToken = $content['access_token'];
    }

    public function generateUrl(string $url): string
    {
        return sprintf('/api/v3%s?access_token=%s', $url, $this->accessToken);
    }
}
