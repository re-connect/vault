<?php

namespace App\Tests\v2\API\v3;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\ClientFactory;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractApiTest extends ApiTestCase
{
    use Factories;

    protected ?BeneficiaireRepository $beneficiaireRepository;
    protected ?string $accessToken = null;

    protected const BASE_URL = '/api/v3';

    protected function setUp(): void
    {
        parent::setUp();
        $this->beneficiaireRepository = $this->getContainer()->get(BeneficiaireRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->beneficiaireRepository = null;
    }

    /**
     * @param array<string, string> $expectedJson
     */
    public function assertEndpoint(string $clientName, string $endpoint, string $method, int $expectedStatusCode, ?array $expectedJson = null, mixed $body = null): void
    {
        $client = static::createClient();

        $this->loginAsClient($client, $clientName);

        $options = ['body' => json_encode($body)];
        if (in_array($method, [Request::METHOD_PATCH, Request::METHOD_POST])) {
            $options['headers'] = ['Content-Type' => 'application/json'];
        }

        $client->request($method, $this->generateUrl($endpoint), $options);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedJson) {
            $this->assertJsonContains($expectedJson);
        }
    }

    public function assertEndpointAccessIsDenied(string $clientName, string $endpoint, string $method, mixed $body = null): void
    {
        $client = static::createClient();

        $this->loginAsClient($client, $clientName);
        $options = ['body' => json_encode($body)];
        if (in_array($method, [Request::METHOD_PATCH, Request::METHOD_POST])) {
            $options['headers'] = ['Content-Type' => 'application/json'];
        }
        $client->request($method, $this->generateUrl($endpoint), $options);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Access Denied.',
        ]);
    }

    public function loginAsClient(Client $client, string $clientName, string $grantType = 'client_credentials'): void
    {
        $apiClient = ClientFactory::find(['nom' => $clientName])->object();

        $response = $client->request('POST', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $apiClient->getRandomId(),
            'client_secret' => $apiClient->getSecret(),
        ]]);

        $content = json_decode($response->getContent(), true);
        $this->accessToken = $content['access_token'];
    }

    public function getBeneficiaryForClient(string $clientName): Beneficiaire
    {
        $client = ClientFactory::find(['nom' => $clientName])->object();

        return $this->beneficiaireRepository->findByClientIdentifier($client->getRandomId())[0];
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
