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

    protected const BASE_URL = '/api/v3';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * @param array<string, string> $expectedJson
     */
    public function assertEndpoint(string $clientName, string $endpoint, string $method, int $expectedStatusCode, ?array $expectedJson = null, mixed $body = null): void
    {
        $this->loginAsClient($clientName);
        $options = ['body' => json_encode($body)];
        if (in_array($method, [Request::METHOD_PATCH, Request::METHOD_POST])) {
            $options['headers'] = ['Content-Type' => 'application/json'];
        }
        $this->client->request($method, $this->generateUrl($endpoint), $options);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedJson) {
            $this->assertJsonContains($expectedJson);
        }
    }

    public function loginAsClient(string $clientName, string $grantType = 'client_credentials'): void
    {
        $client = ClientFactory::find(['nom' => $clientName])->object(); // We use the same value in old client table to access properties easily

        $response = $this->client->request('POST', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $client->getRandomId(),
            'client_secret' => $client->getSecret(),
            'scope' => 'centers beneficiaries documents notes contacts pros events users',
        ]]);

        $content = json_decode($response->getContent(), true);
        $this->accessToken = $content['access_token'];
    }

    public function generateUrl(string $url): string
    {
        return sprintf('%s%s%saccess_token=%s', self::BASE_URL, $url, $this->getQueryDelimiter($url), $this->accessToken);
    }

    private function getQueryDelimiter(string $url): string
    {
        return str_contains($url, '?') ? '&' : '?';
    }
}
