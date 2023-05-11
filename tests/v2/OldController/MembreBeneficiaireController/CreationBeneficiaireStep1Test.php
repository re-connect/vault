<?php

namespace App\Tests\v2\OldController\MembreBeneficiaireController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class CreationBeneficiaireStep1Test extends AbstractControllerTest implements TestRouteInterface
{
    private const URL_DEFAULT = '/membre/beneficiaires/creation-beneficiaire/default/etape-1';
    private const URL_REMOTELY = '/membre/beneficiaires/creation-beneficiaire/remotely/etape-1';

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'default - Should redirect to login when not authenticated' => [self::URL_DEFAULT, 302, null, '/login'];
        yield 'default - Should redirect code when authenticated as beneficiaire' => [self::URL_DEFAULT, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/home'];
        yield 'default - Should return 200 status code when authenticated as member' => [self::URL_DEFAULT, 200, MemberFixture::MEMBER_MAIL];
        yield 'remotely - Should redirect to login when not authenticated' => [self::URL_REMOTELY, 302, null, '/login'];
        yield 'remotely - redirect code when authenticated as beneficiaire' => [self::URL_REMOTELY, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/home'];
        yield 'remotely - return 200 status code when authenticated as member' => [self::URL_REMOTELY, 200, MemberFixture::MEMBER_MAIL];
    }
}
