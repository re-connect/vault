<?php

namespace App\Tests\v2\Command;

use App\Command\ApiUsageReportCommand;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ApiUsageReportCommandTest extends TestCase
{
    private string $logDirectory;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->logDirectory = sys_get_temp_dir().'/api_usage_test_'.uniqid();
        $this->filesystem->dumpFile($this->logDirectory.'/prod-2026-01-01.log', implode("\n", [
            $this->line('-2 days', 're_api_user_get_mine', 'v2', 'mobile-id', 'ROLE_BENEFICIAIRE'),
            $this->line('-1 day', 're_api_user_get_mine', 'v2', 'mobile-id', 'ROLE_BENEFICIAIRE'),
            $this->line('-1 day', 're_api_user_get_mine', 'legacy', 'mobile-id', 'ROLE_BENEFICIAIRE'),
            $this->line('-90 days', 'oauth_server_token_post_old', 'oauth', null, 'anonymous'),
            '{"message":"another message","context":{},"datetime":"2026-01-01T00:00:00+00:00"}',
            'not json',
        ]));
        $this->filesystem->dumpFile($this->logDirectory.'/prod-2026-01-02.log', $this->line('-3 hours', '_api_/v3/beneficiaries{._format}_get_collection', 'v3', 'rosalie-id', 'client'));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->logDirectory);
    }

    public function testAggregatesCallsPerRouteVersionClientAndUserType(): void
    {
        $output = $this->execute([]);

        $this->assertMatchesRegularExpression('/re_api_user_get_mine\s+v2\s+applimobile\s+ROLE_BENEFICIAIRE\s+2\s/', $output);
        $this->assertMatchesRegularExpression('/re_api_user_get_mine\s+legacy\s+applimobile\s+ROLE_BENEFICIAIRE\s+1\s/', $output);
        $this->assertMatchesRegularExpression('/_api_\/v3\/beneficiaries\S+\s+v3\s+rosalie-id\s+client\s+1\s/', $output);
        $this->assertStringNotContainsString('oauth_server_token_post_old', $output);
    }

    public function testFiltersOnDaysAndApiVersions(): void
    {
        $output = $this->execute(['--days' => '100', '--api-version' => ['legacy', 'oauth']]);

        $this->assertStringContainsString('oauth_server_token_post_old', $output);
        $this->assertMatchesRegularExpression('/re_api_user_get_mine\s+legacy/', $output);
        $this->assertDoesNotMatchRegularExpression('/\sv2\s/', $output);
        $this->assertStringNotContainsString('_api_/v3', $output);
    }

    /** @param array<string, mixed> $options */
    private function execute(array $options): string
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager->method('find')->willReturnCallback(
            fn (string $id) => 'mobile-id' === $id ? new Client('applimobile', $id, 'secret') : null,
        );

        $application = new Application();
        $application->addCommand(new ApiUsageReportCommand($this->logDirectory, $clientManager));
        $commandTester = new CommandTester($application->find('app:api:usage-report'));
        $commandTester->execute($options);
        $this->assertSame(0, $commandTester->getStatusCode());

        return $commandTester->getDisplay();
    }

    private function line(string $when, string $route, string $version, ?string $clientId, string $userType): string
    {
        return json_encode([
            'message' => 'api_call',
            'context' => ['route' => $route, 'version' => $version, 'method' => 'GET', 'status' => 200, 'client_id' => $clientId, 'user_type' => $userType],
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'api_usage',
            'datetime' => (new \DateTimeImmutable($when))->format(\DateTimeInterface::RFC3339_EXTENDED),
            'extra' => [],
        ]);
    }
}
