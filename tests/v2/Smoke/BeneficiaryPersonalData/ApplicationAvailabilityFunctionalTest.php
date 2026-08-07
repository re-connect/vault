<?php

namespace App\Tests\v2\Smoke\BeneficiaryPersonalData;

use App\Tests\v2\Smoke\AbstractSmokeTest;
use Zenstruck\Foundry\Test\Factories;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    use Factories;

    /**
     * @dataProvider beneficiaryUrlProvider
     */
    public function testBeneficiaryPages(string $url, bool $beneficiaryOnly = false): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($this->beneficiary->getUser());
        $this->assertRoute($client, sprintf($url, $this->beneficiary->getId()));

        if (!$beneficiaryOnly) {
            $client->loginUser($this->professional->getUser());
            $this->assertRoute($client, sprintf($url, $this->beneficiary->getId()));
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
