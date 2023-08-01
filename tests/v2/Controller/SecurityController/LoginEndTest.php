<?php

namespace App\Tests\v2\Controller\SecurityController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class LoginEndTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/login-end';

    public function provideTestRoute(): \Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect to redirection route when authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/user/redirect-user/'];
        yield 'Should redirect to redirection route when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/user/redirect-user/'];
        yield 'Should redirect to password update route when authenticated as member with expired password' => [self::URL, 302, MemberFixture::MEMBER_PASSWORD_EXPIRED_MAIL, '/update-password'];
        yield 'Should redirect to password update route when authenticated as member with overdue password' => [self::URL, 302, MemberFixture::MEMBER_PASSWORD_OVERDUE_MAIL, '/update-password'];
        yield 'Should redirect to beneficiary creation when authenticated as member with no manage beneficiary rights' => [self::URL, 302, MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION, '/user/redirect-user/'];
    }

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
}
