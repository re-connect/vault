<?php

namespace App\Tests\v2\Controller\Security;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Zenstruck\Foundry\Test\Factories;

/**
 * These routes were reachable without /api prefix, through the web session firewall, without any access control.
 */
class LegacyUnprefixedApiRoutesTest extends AbstractControllerTest
{
    use Factories;

    public function provideRoutes(): \Generator
    {
        yield 'Documents of any beneficiary' => [fn () => sprintf('/beneficiaries/%d/documents', BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->getId())];
        yield 'Beneficiaries of the connected pro' => [fn () => '/beneficiaries/mine'];
    }

    /** @dataProvider provideRoutes */
    public function testRouteDoesNotExist(\Closure $url): void
    {
        $this->assertRoute($url(), 404, MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION);
    }
}
