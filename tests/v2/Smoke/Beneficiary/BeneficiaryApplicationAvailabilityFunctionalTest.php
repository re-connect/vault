<?php

namespace App\Tests\v2\Smoke\Beneficiary;

use App\Tests\v2\Smoke\AbstractSmokeTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class BeneficiaryApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->loginUser($this->beneficiary->getUser());
    }

    /**
     * @dataProvider beneficiaryUrlProvider
     */
    public function testBeneficiaryPages(string $url): void
    {
        $this->assertRoute($this->client, $url);
    }

    public function beneficiaryUrlProvider(): \Generator
    {
        yield ['/beneficiary'];
    }
}
