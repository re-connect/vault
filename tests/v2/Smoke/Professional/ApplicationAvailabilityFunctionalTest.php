<?php

namespace App\Tests\v2\Smoke\Professional;

use App\Tests\v2\Smoke\AbstractSmokeTest;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
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
        $this->assertRoute(sprintf($url, $this->beneficiary->getId()));
    }

    public function professionalUrlProvider(): \Generator
    {
        yield ['/beneficiary/create'];
        yield ['/beneficiary/affiliate'];
        yield ['/beneficiary/affiliate/search'];
        yield ['/beneficiaries'];
    }
}
