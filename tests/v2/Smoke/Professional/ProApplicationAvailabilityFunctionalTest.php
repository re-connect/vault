<?php

namespace App\Tests\v2\Smoke\Professional;

use App\Tests\v2\Smoke\AbstractSmokeTest;

class ProApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->loginUser($this->professional->getUser());
    }

    /**
     * @dataProvider professionalUrlProvider
     */
    public function testProfessionalPages(string $url): void
    {
        $this->assertRoute($url);
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
