<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\AdminFixture;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;
use Zenstruck\Foundry\Test\Factories;

class ResetMfaRetryCount extends AbstractControllerTest implements TestRouteInterface
{
    use Factories;
    private const URL = '/user/%s/reset-mfa-retry-count';

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
        $url = sprintf($url, UserFactory::random()->object()->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as member' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 302 status code when authenticated as admin' => [self::URL, 302, AdminFixture::ADMIN_MAIL];
    }

    public function testShouldResetMfaRetryCount(): void
    {
        $client = self::createClient();
        $adminUser = UserFactory::findByEmail(AdminFixture::ADMIN_MAIL)->object();
        $client->loginUser($adminUser);

        // User has 2 retry
        $user = UserFactory::random()->object();
        $user->setMfaRetryCount(2);
        $this->getEntityManager()->flush();
        self::assertEquals(2, UserFactory::find($user)->object()->getMfaRetryCount());

        // User has 0 retry after request
        $client->request('GET', sprintf(self::URL, $user->getId()));
        self::assertResponseRedirects();
        self::assertEquals(0, UserFactory::find($user)->object()->getMfaRetryCount());
    }
}
