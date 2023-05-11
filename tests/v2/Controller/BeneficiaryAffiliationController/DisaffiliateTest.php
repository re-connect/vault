<?php

namespace App\Tests\v2\Controller\BeneficiaryAffiliationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class DisaffiliateTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/%s/disaffiliate/choose-relay';
    private const FORM_VALUES = [
        'disaffiliate_beneficiary[relays][0]' => '',
        'disaffiliate_beneficiary[relays][1]' => '',
    ];

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
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code on disaffiliation choice page when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 302 status code when authenticated as member with no relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
    }

    public function testDisaffiliateAjaxCall(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $relays = $beneficiary->getCentres();
        $relaysCount = count($relays);
        $url = sprintf('/beneficiary/%s/relay/%s/disaffiliate', $beneficiary->getId(), $relays[0]->getId());

        $this->assertRoute($url, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, null, 'GET', true);

        self::assertLessThan($relaysCount, $relaysCount - 1);
    }
}
