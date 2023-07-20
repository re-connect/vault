<?php

namespace App\Tests\v2\Controller\BeneficiaryAffiliationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SendSmsAffiliationCodeTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/%s/affiliate/send-invitation-sms-code';
    private const RELAYS_URL = '/beneficiary/%s/affiliate/relays';

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        string $userMail = null,
        string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf($url, $beneficiary->getId());
        $expectedRedirect = sprintf($expectedRedirect, $beneficiary->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect to relays choice when authenticated as professional' => [self::URL, 302, MemberFixture::MEMBER_MAIL, self::RELAYS_URL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }
}
