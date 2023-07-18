<?php

namespace App\Tests\v2\Smoke\Beneficiary;

use App\Tests\v2\Smoke\AbstractSmokeTest;

class BeneficiaryApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->loginUser($this->beneficiary->getUser());
    }

    /**
     * @dataProvider beneficiaryUrlProvider
     */
    public function testBeneficiaryPages(string $url): void
    {
        $this->assertRoute($url);
    }

    public function beneficiaryUrlProvider(): \Generator
    {
        yield ['/beneficiary'];
    }
}
