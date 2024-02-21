<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;
use Zenstruck\Foundry\Test\Factories;

class CgsTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    use Factories;
    private const URL = '/user/cgs';

    private const FORM_VALUES = [
        'first_visit[accept]' => true,
        'first_visit[mfaEnabled]' => false,
        'first_visit[mfaMethod]' => 'email',
    ];

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
        yield 'Should return 200 status code when authenticated as member in first visit' => [self::URL, 200, MemberFixture::MEMBER_FIRST_VISIT];
        yield 'Should return 200 status code when authenticated as beneficiary in first visit' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT];
        yield 'Should return 302 status code when authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/user/redirect-user/'];
        yield 'Should return 302 status code when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/user/redirect-user/'];
    }

    /** @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $user = UserFactory::findByEmail($email)->object();
        // Check that use has not accpted terms of use
        self::assertTrue($user->isFirstVisit());
        self::assertNull($user->getCgsAcceptedAt());

        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);

        $user = UserFactory::findByEmail($email)->object();
        // Then check that user has accepted terms of use
        $this->assertNotNull($user->getCgsAcceptedAt());
        // Check if first visit process is over
        $this->assertFalse($user->isFirstVisit());
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect when form is correct for beneficiary' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            MemberFixture::MEMBER_FIRST_VISIT,
            '/user/redirect-user/',
        ];

        yield 'Should redirect when form is correct for pro' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT,
            '/user/redirect-user/',
        ];
        $values = self::FORM_VALUES;
        $values['first_visit[mfaEnabled]'] = true;
        yield 'Should redirect when form is correct for pro with mfa enabled' => [
            self::URL,
            'confirm',
            $values,
            BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT,
            '/user/redirect-user/',
        ];
    }

    /** @dataProvider provideTestFormIsNotValid */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, string $alternateSelector = null): void
    {
        $this->assertFormIsNotValid(self::URL, 'user_cgs', 'confirm', $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        yield 'Should return 422 status code with beneficiary when cgs are not accepted' => [self::URL, 'user_cgs', 'confirm', [], [['message' => 'Vous devez accepter les conditions d\'utilisation']], BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT];
        yield 'Should return 422 status code with pro when cgs are not accepted' => [self::URL, 'user_cgs', 'confirm', [], [['message' => 'Vous devez accepter les conditions d\'utilisation']], MemberFixture::MEMBER_FIRST_VISIT];
        $values = self::FORM_VALUES;
        $values['first_visit[accept]'] = true;
        $values['first_visit[mfaEnabled]'] = true;
        $values['first_visit[mfaMethod]'] = 'sms';
        yield 'Should return 422 status code with pro when choosing sms 2FA without telephone' => [self::URL, 'user_cgs', 'confirm', $values, [['message' => 'Vous avez choisi de recevoir un code de connexion par SMS, mais vous n\'avez pas renseigné de numéro de téléphone.']], MemberFixture::MEMBER_FIRST_VISIT];
        yield 'Should return 422 status code with beneficiary when choosing sms 2FA without telephone' => [self::URL, 'user_cgs', 'confirm', $values, [['message' => 'Vous avez choisi de recevoir un code de connexion par SMS, mais vous n\'avez pas renseigné de numéro de téléphone.']], BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT];
    }
}
