<?php

namespace App\Tests\v2\Smoke\BeneficiaryPersonalData;

use App\Tests\v2\Smoke\AbstractSmokeTest;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    /**
     * @dataProvider beneficiaryUrlProvider
     */
    public function testBeneficiaryPages(string $url, bool $beneficiaryOnly = false): void
    {
        $this->client->loginUser($this->beneficiary->getUser());
        $this->assertRoute(sprintf($url, $this->beneficiary->getId()));

        if (!$beneficiaryOnly) {
            $this->client->loginUser($this->professional->getUser());
            $this->assertRoute(sprintf($url, $this->beneficiary->getId()));
        }
    }

    public function beneficiaryUrlProvider(): \Generator
    {
        yield ['/beneficiary/%d/contacts'];
        yield ['/beneficiary/%d/contacts/create'];
        yield ['/beneficiary/%d/notes'];
        yield ['/beneficiary/%d/notes/create'];
        yield ['/beneficiary/%d/events'];
        yield ['/beneficiary/%d/events/create'];
        yield ['/beneficiary/%d/documents'];
        yield ['/beneficiary/%d/folders/create'];
        yield ['/relays/mine', true];
        yield ['/user/settings'];
        yield ['/user/delete', true];
    }
}
