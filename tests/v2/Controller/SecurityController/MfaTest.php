<?php

namespace App\Tests\v2\Controller\SecurityController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Repository\UserRepository;
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

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame(self::MFA_ROUTE);
    }

    public function provideTestShouldRedirectToMfaAfterLogin(): \Generator
    {
        yield 'Should redirect to 2FA form as Member' => [MemberFixture::MEMBER_WITH_MFA_ENABLE];
        yield 'Should redirect to 2FA form as Beneficiary' => [BeneficiaryFixture::BENEFICIARY_WITH_MFA_ENABLE];
    }

    /** @dataProvider provideTestShouldNotRedirectToMfaAfterLogin */
    public function testShouldNotRedirectToMfaAfterLogin(string $email, string $route): void
    {
        $this->submitLoginForm($email);

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame($route);
    }

    public function provideTestShouldNotRedirectToMfaAfterLogin(): \Generator
    {
        yield 'Should redirect to beneficiaries list as Member' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS, 'list_beneficiaries'];
        yield 'Should redirect to vault home as Beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL, 'beneficiary_home'];
    }

    public function testShouldNotValidFormIfAuthCodeIsIncorrect(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertRouteSame(self::MFA_ROUTE);

        $form = $this->crawler->selectButton('Valider')->form();
        $form->setValues([
            '_auth_code' => 'abcd',
        ]);
        $this->client->submit($form);

        $this->assertRouteSame(self::MFA_ROUTE);
        $this->assertSelectorTextContains('div.alert', 'Le code est incorrect');
    }

    public function testShouldValidFormIfAuthCodeIsIncorrect(): void
    {
        $this->submitLoginForm(MemberFixture::MEMBER_WITH_MFA_ENABLE);
        $this->assertRouteSame(self::MFA_ROUTE);

        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $user = $repo->findOneBy(['email' => MemberFixture::MEMBER_WITH_MFA_ENABLE]);

        $form = $this->crawler->selectButton('Valider')->form();
        $form->setValues([
            '_auth_code' => $user->getEmailAuthCode(),
        ]);
        $this->client->submit($form);

        $this->assertRouteSame('affiliate_beneficiary_home');
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
}
