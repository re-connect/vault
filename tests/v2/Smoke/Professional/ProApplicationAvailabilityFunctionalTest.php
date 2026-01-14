<?php

namespace App\Tests\v2\Smoke\Professional;

use App\Tests\v2\Smoke\AbstractSmokeTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ProApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->loginUser($this->professional->getUser());
    }

    /**
     * @dataProvider professionalUrlProvider
     */
    public function testProfessionalPages(string $url): void
    {
        $this->assertRoute($this->client, $url);
    }

    public function professionalUrlProvider(): \Generator
    {
        yield ['/beneficiary/create'];
        yield ['/beneficiary/affiliate'];
        yield ['/beneficiary/affiliate/search'];
        yield ['/beneficiaries'];
        yield ['/pro/create/home'];
    }
}
