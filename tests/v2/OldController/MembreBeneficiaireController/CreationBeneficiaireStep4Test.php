<?php

namespace App\Tests\v2\OldController\MembreBeneficiaireController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class CreationBeneficiaireStep4Test extends AbstractControllerTest implements TestRouteInterface
{
    private const URL_DEFAULT = '/membre/beneficiaires/creation-beneficiaire/default/etape-4';

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
        yield 'Should redirect to login when not authenticated' => [self::URL_DEFAULT, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL_DEFAULT, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should redirect to step 1 when authenticated as member' => [self::URL_DEFAULT, 302, MemberFixture::MEMBER_MAIL, '/membre/beneficiaires/ajout-beneficiaire'];
    }
}
