<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class RequestAccountDataTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/user/request-personal-account-data';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect user when authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/user/redirect-user/'];
        yield 'Should return 200 status code when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
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

    public function testEmailIsSend(): void
    {
        $client = self::createClient();
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $client->loginUser($user);
        $client->request('GET', self::URL);

        self::assertEmailCount(1);
        self::assertEmailTextBodyContains(
            self::getMailerMessage(),
            sprintf('Un utilisateur (id user = %d) vient d’effectuer une demande de récupération de ses données sur le coffre-fort numérique', $user->getId()),
        );
    }

    /** @dataProvider provideTestBeneficiaryNeedsEmailOrTelephoneToSendRequest */
    public function testBeneficiaryNeedsEmailOrTelephoneToSendRequest(bool $isSent, ?string $email, ?string $phone): void
    {
        $client = self::createClient();
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $client->loginUser($user);

        $user->setTelephone($phone)->setEmail($email);
        $client->request('GET', self::URL);
        self::assertEmailCount($isSent ? 1 : 0);
    }

    public function provideTestBeneficiaryNeedsEmailOrTelephoneToSendRequest(): \Generator
    {
        yield 'Can request data with only phone' => [true, null, '0600000000'];
        yield 'Can request data with only email' => [true, 'email@mail.com', null];
        yield 'Can not request data without email and phone' => [false, null, null];
    }
}
