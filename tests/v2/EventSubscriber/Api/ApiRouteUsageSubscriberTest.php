<?php

namespace App\Tests\v2\EventSubscriber\Api;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\EventSubscriber\Api\ApiRouteUsageSubscriber;
use App\Tests\Factory\ClientFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;

class ApiRouteUsageSubscriberTest extends AbstractPasswordGrantApiTestCase
{
    public function provideVersions(): \Generator
    {
        yield 'v3' => ['/api/v3/beneficiaries', 'v3'];
        yield 'v2' => ['/api/v2/user', 'v2'];
        yield 'Unversioned legacy route' => ['/api/beneficiaires', 'legacy'];
        yield 'OAuth token' => ['/oauth/v2/token', 'oauth'];
        yield 'League token' => ['/api/token', 'oauth'];
        yield 'Rosalie interoperability' => ['/appli/rosalie/beneficiaries', 'appli'];
        yield 'Public folder icons' => ['/public/folder_icons', 'public'];
        yield 'Web page' => ['/user/settings', null];
    }

    /** @dataProvider provideVersions */
    public function testGetVersion(string $path, ?string $expectedVersion): void
    {
        $this->assertSame($expectedVersion, ApiRouteUsageSubscriber::getVersion($path));
    }

    public function testLogsUserCallsWithRouteVersionAndClient(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $applimobileId = ClientFactory::find(['nom' => 'applimobile'])->getRandomId();

        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);
        $this->assertSame(
            ['route' => 'oauth_server_token_post_old', 'version' => 'oauth', 'method' => 'GET', 'status' => 200, 'client_id' => null, 'user_type' => 'anonymous'],
            $this->getLastUsageContext(),
        );

        $client->request(Request::METHOD_GET, sprintf('/api/v2/user?access_token=%s', $this->accessToken));
        $this->assertSame(
            ['route' => 're_api_user_get_mine', 'version' => 'v2', 'method' => 'GET', 'status' => 200, 'client_id' => $applimobileId, 'user_type' => User::USER_TYPE_BENEFICIAIRE],
            $this->getLastUsageContext(),
        );

        $client->request(Request::METHOD_GET, sprintf('/api/beneficiaires?access_token=%s', $this->accessToken));
        $this->assertSame(
            ['route' => 're_api_beneficiaire_list_for_pro', 'version' => 'legacy', 'method' => 'GET', 'status' => 403, 'client_id' => $applimobileId, 'user_type' => User::USER_TYPE_BENEFICIAIRE],
            $this->getLastUsageContext(),
        );
    }

    public function testLogsClientCredentialsCalls(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $apiClient = ClientFactory::find(['nom' => 'rosalie']);
        $response = $client->request(Request::METHOD_POST, '/oauth/v2/token', ['json' => [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getRandomId(),
            'client_secret' => $apiClient->getSecret(),
        ]]);

        $client->request(Request::METHOD_GET, sprintf('/api/v3/beneficiaries?access_token=%s', $response->toArray()['access_token']));

        $context = $this->getLastUsageContext();
        $this->assertSame('v3', $context['version']);
        $this->assertSame('client', $context['user_type']);
        $this->assertSame($apiClient->getRandomId(), $context['client_id']);
        $this->assertSame(200, $context['status']);
    }

    public function testDoesNotLogWebPages(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request(Request::METHOD_GET, '/login');

        $this->assertSame([], $this->getUsageRecords());
    }

    /** @return array<string, mixed> */
    private function getLastUsageContext(): array
    {
        $records = $this->getUsageRecords();
        $this->assertCount(1, $records);

        return $records[0]->context;
    }

    /** @return LogRecord[] */
    private function getUsageRecords(): array
    {
        /** @var TestHandler $handler */
        $handler = self::getContainer()->get('monolog.handler.api_usage');

        return $handler->getRecords();
    }
}
