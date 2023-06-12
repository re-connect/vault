<?php

namespace App\Tests\v2\Controller\BeneficiaryResetPasswordController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\ResetPasswordRequestFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SMSTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiaries/%s/reset-password/sms';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf(self::URL, $beneficiary->getId());

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function testResetPasswordRequestIsSend(): void
    {
        $client = self::createClient();
        $client->loginUser(MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES)->object()->getUser());
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $client->request('GET', sprintf(self::URL, $beneficiary->getId()));

        // Check request is created with correct user
        $resetPasswordRequest = ResetPasswordRequestFactory::last()->object();
        self::assertEquals($beneficiary->getUser()->getId(), $resetPasswordRequest->getUser()->getId());

        // Check that password request is SMS request
        self::assertNotNull($resetPasswordRequest->getSmsCode());
        self::assertNotNull($resetPasswordRequest->getSmsToken());
    }
}
