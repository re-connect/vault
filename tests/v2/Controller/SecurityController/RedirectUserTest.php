<?php

namespace App\Tests\v2\Controller\SecurityController;

use App\DataFixtures\v2\AdminFixture;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class RedirectUserTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/user/redirect-user/';

    public function provideTestRoute(): \Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect to admin dashboard when authenticated as admin' => [self::URL, 302, AdminFixture::ADMIN_MAIL, '/admin/dashboard'];
        yield 'Should redirect to first visit page when authenticated for the first time as Professionnal' => [self::URL, 302, MemberFixture::MEMBER_FIRST_VISIT, '/user/first-visit'];
        yield 'Should redirect to first visit page when authenticated for the first time as Beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT, '/user/first-visit'];
        yield 'Should redirect to beneficiary home when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary'];
        yield 'Should redirect to beneficiary creation when authenticated as Professional with no manage beneficiary rights' => [self::URL, 302, MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION, '/beneficiary/affiliate'];
        yield 'Should redirect to beneficiary list when authenticated as Professional with manage beneficiary rights' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/beneficiaries'];
    }

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

    public function testProFirstVisitWithExpiredPasswordShouldBeRedirected(): void
    {
        self::ensureKernelShutdown();
        $clientTest = static::createClient();
        $clientTest->followRedirects();

        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_FIRST_VISIT_AND_EXPIRED_PASSWORD);
        $clientTest->loginUser($user);
        $clientTest->request('GET', self::URL);

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('user_first_visit');
    }
}
