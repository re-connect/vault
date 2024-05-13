<?php

namespace App\Tests\v2\Controller\SecurityController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class MfaTest extends AbstractControllerTest
{
    private const LOGIN_URL = '/login';
    private const MFA_ROUTE = '2fa_login';

    private Crawler $crawler;
    private KernelBrowser $client;

    /** @dataProvider provideTestShouldRedirectToMfaAfterLogin */
    public function testShouldRedirectToMfaAfterLogin(string $email): void
    {
        $this->submitLoginForm($email);
        $this->assertEmailCount(1);

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame(self::MFA_ROUTE);
    }

    public function provideTestShouldRedirectToMfaAfterLogin(): \Generator
    {
        yield 'Should redirect to 2FA form as Member with mfa enabled' => [MemberFixture::MEMBER_WITH_MFA_ENABLE];
        yield 'Should redirect to 2FA form as Beneficiary with mfa enabled' => [BeneficiaryFixture::BENEFICIARY_WITH_MFA_ENABLE];
    }

    /** @dataProvider provideTestShouldNotRedirectToMfaAfterLogin */
    public function testShouldNotRedirectToMfaAfterLogin(string $email, string $route): void
    {
        $this->submitLoginForm($email);
        $this->assertEmailCount(0);

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame($route);
    }

    public function provideTestShouldNotRedirectToMfaAfterLogin(): \Generator
    {
        yield 'Should redirect to beneficiaries list as Member with mfa disable' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS, 'list_beneficiaries'];
        yield 'Should redirect to vault home as Beneficiary with mfa disable' => [BeneficiaryFixture::BENEFICIARY_MAIL, 'beneficiary_home'];
    }

    public function testShouldNotValidFormIfAuthCodeIsIncorrect(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertEmailCount(1);
        $this->assertRouteSame(self::MFA_ROUTE);

        $this->submitMfaForm('abcd');

        $this->assertRouteSame(self::MFA_ROUTE);
        $this->assertSelectorTextContains('div.alert', 'Le code est incorrect');
    }

    public function testShouldValidFormIfAuthCodeIsCorrect(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertEmailCount(1);
        $this->assertRouteSame(self::MFA_ROUTE);

        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->submitMfaForm($user->getEmailAuthCode());

        $this->assertRouteSame('affiliate_beneficiary_home');
    }

    public function testResendAuthCode(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertEmailCount(1);
        $this->assertRouteSame(self::MFA_ROUTE);
        $this->client->followRedirects(false); // no redirects so we can assert email count when clicking link

        // Save auth code so we can assert a new one has been generated
        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $oldAuthCode = $user->getEmailAuthCode();

        $this->client->clickLink('Cliquez ici pour le renvoyer');
        $this->assertEmailCount(1);

        $this->client->request('GET', '/2fa');
        $this->assertRouteSame(self::MFA_ROUTE);
        $this->client->followRedirects();

        // Assert that the old code no longer works
        $this->submitMfaForm($oldAuthCode);

        $this->assertRouteSame(self::MFA_ROUTE);
        $this->assertSelectorTextContains('div.alert', 'Le code est incorrect');
        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_WITH_MFA_ENABLE);

        // Assert that the newly received code is working
        $this->submitMfaForm($user->getEmailAuthCode());

        $this->assertRouteSame('affiliate_beneficiary_home');
    }

    public function testResendAuthCodeLimit(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertEmailCount(1);
        $this->assertRouteSame(self::MFA_ROUTE);
        // Click resend link 3 times
        for ($i = 0; $i <= User::MFA_MAX_SEND_CODE_COUNT; ++$i) {
            $this->client->clickLink('Cliquez ici pour le renvoyer');
        }

        $this->assertRouteSame(self::MFA_ROUTE);
        $this->assertSelectorTextContains('div.alert', 'Vous avez atteint le nombre maximum de renvois autorisés. Si vous ne parvenez pas à vous connecter, veuillez contacter le support.');
    }

    private function submitLoginForm(string $email): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->crawler = $this->client->request('GET', self::LOGIN_URL);
        $this->assertResponseIsSuccessful();
        $this->client->followRedirects();
        $form = $this->crawler->selectButton('Connexion')->form();
        $form->setValues([
            '_username' => $email,
            '_password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]);
        $this->crawler = $this->client->submit($form);
    }

    private function submitMfaForm(string $authCode): void
    {
        $form = $this->crawler->selectButton('Valider')->form();
        $form->setValues([
            '_auth_code' => $authCode,
        ]);
        $this->client->submit($form);
    }
}
