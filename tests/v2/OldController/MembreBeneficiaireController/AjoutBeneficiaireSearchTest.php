<?php

namespace App\Tests\v2\OldController\MembreBeneficiaireController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class AjoutBeneficiaireSearchTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/membre/beneficiaires/ajout-beneficiaire';

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
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 302 status code when authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/beneficiary/affiliate'];
    }
}
