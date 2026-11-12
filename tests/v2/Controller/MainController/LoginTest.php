<?php

namespace App\Tests\v2\Controller\MainController;

use App\DataFixtures\v2\AdminFixture;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class LoginTest extends WebTestCase
{
    use Factories;

    private const string OLD_USERNAME = 'old.username.login-test';

    /** @dataProvider provideCanLoginOnPage */
    public function testCanLoginOnPage(string $url, string $email): void
    {
        $client = static::createClient();

        $this->submitLoginForm($client, $url, $email);

        $this->assertResponseRedirects('/login-end');
    }

    public static function provideCanLoginOnPage(): \Generator
    {
        yield 'Should be able to login on Home page as Benef' => ['/', BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should be able to login on Vault page as Benef' => ['/reconnect-le-coffre-fort-numerique', BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should be able to login on Home page as Pro' => ['/', MemberFixture::MEMBER_MAIL];
        yield 'Should be able to login on Vault page as Pro' => ['/reconnect-le-coffre-fort-numerique', MemberFixture::MEMBER_MAIL];
        yield 'Should be able to login on Home page as Admin' => ['/', AdminFixture::ADMIN_MAIL];
        yield 'Should be able to login on Vault page as Admin' => ['/reconnect-le-coffre-fort-numerique', AdminFixture::ADMIN_MAIL];
    }

    /** @dataProvider provideUserWithOldUsername */
    public function testCanLoginWithCurrentUsernameWhenUserHasOldUsername(string $email): void
    {
        $client = static::createClient();
        $user = $this->setOldUsername($email);

        $this->submitLoginForm($client, '/login', $user->getUsername());

        $this->assertResponseRedirects('/login-end');
    }

    /** @dataProvider provideUserWithOldUsername */
    public function testCannotLoginWithOldUsername(string $email): void
    {
        $client = static::createClient();
        $this->setOldUsername($email);

        $this->submitLoginForm($client, '/login', self::OLD_USERNAME);

        $this->assertResponseRedirects('/login');
    }

    public static function provideUserWithOldUsername(): \Generator
    {
        yield 'Member' => [MemberFixture::MEMBER_MAIL];
        yield 'Beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    private function submitLoginForm(KernelBrowser $client, string $url, string $identifier): void
    {
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Connexion')->form();
        $form->setValues([
            '_username' => $identifier,
            '_password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]);
        $client->submit($form);
    }

    private function setOldUsername(string $email): User
    {
        $user = UserFactory::findByEmail($email);
        $user->setOldUsername(self::OLD_USERNAME);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        return $user;
    }
}
