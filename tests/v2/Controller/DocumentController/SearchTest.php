<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SearchTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/%s/documents/search';

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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getId());

        $this->assertRoute(
            $url,
            $expectedStatusCode,
            $userMail,
            $expectedRedirect,
            'POST',
            true,
            [
                'search' => [
                    'search' => 'test',
                ],
            ]
        );
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should redirect when authenticated as an other beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, '/beneficiary/home'];
        yield 'Should redirect when authenticated as member with no relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
    }
}
