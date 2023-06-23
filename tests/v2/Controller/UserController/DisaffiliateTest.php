<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class DisaffiliateTest extends AbstractControllerTest
{
    private const URL = '/user/%s/disaffiliate/choose-relay';

    /** @dataProvider provideTestRouteForBeneficiaryUser */
    public function testRouteForBeneficiaryUser(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
    ): void {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $user->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRouteForBeneficiaryUser(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code on disaffiliation choice page when authenticated as pro' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    /** @dataProvider provideTestRouteForProUser */
    public function testRouteForProUser(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
    ): void {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $url = sprintf($url, $user->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRouteForProUser(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code on disaffiliation choice page when authenticated as pro' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
    }

    public function testDisaffiliateAjaxCall(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $relays = $user->getCentres();
        $relaysCount = count($relays);
        $url = sprintf('/user/%s/relay/%s/disaffiliate', $user->getId(), $relays[0]->getId());

        $this->assertRoute($url, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER, null, 'GET', true);

        self::assertLessThan($relaysCount, $relaysCount - 1);
    }
}
