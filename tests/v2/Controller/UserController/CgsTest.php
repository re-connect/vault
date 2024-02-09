<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;
use Zenstruck\Foundry\Test\Factories;

class CgsTest extends AbstractControllerTest implements TestRouteInterface
{
    use Factories;
    private const URL = '/user/cgs';

    private const FORM_VALUES = [
        'cgs[accept]' => '1',
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
        $this->assertEquals($user->isFirstVisit(), !$user->isMfaEnabled());
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect when form is correct for beneficiary' => [
            self::URL,
            'continue',
            self::FORM_VALUES,
            MemberFixture::MEMBER_FIRST_VISIT,
            '/user/mfa',
        ];

        yield 'Should redirect when form is correct for pro' => [
            self::URL,
            'continue',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT,
            '/user/mfa',
        ];
    }

    /** @dataProvider provideTestSubmitFormWithoutAcceptingTermsOfUse */
    public function testSubmitFormWithoutAcceptingTermsOfUse(string $email): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        if ($email) {
            $user = $this->getTestUserFromDb($email);
            $client->loginUser($user);
        }
        $crawler = $client->request('GET', self::URL);
        $form = $crawler->selectButton(self::$translator->trans('continue'))->form();
        $client->submit($form);

        self::assertSelectorTextContains('span.help-block', "Vous devez accepter les conditions d'utilisation");
        $this->assertResponseStatusCodeSame(422);
        $this->assertRouteSame('user_cgs');
    }

    public function provideTestSubmitFormWithoutAcceptingTermsOfUse(): ?\Generator
    {
        yield 'Should return 422 status code with beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL_FIRST_VISIT];
        yield 'Should return 422 status code with pro' => [MemberFixture::MEMBER_FIRST_VISIT];
    }
}
