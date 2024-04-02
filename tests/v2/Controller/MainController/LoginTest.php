<?php

namespace App\Tests\v2\Controller\MainController;

use App\DataFixtures\v2\AdminFixture;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    /** @dataProvider provideCanLoginOnPage */
    public function testCanLoginOnPage(string $url, string $email): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Connexion')->form();
        $form->setValues([
            '_username' => $email,
            '_password' => 'StrongPassword1!',
        ]);
        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/login-end');
    }

    public function provideCanLoginOnPage(): \Generator
    {
        yield 'Should be able to login on Home page as Benef' => ['/', BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should be able to login on Vault page as Benef' => ['/reconnect-le-coffre-fort-numerique', BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should be able to login on Home page as Pro' => ['/', MemberFixture::MEMBER_MAIL];
        yield 'Should be able to login on Vault page as Pro' => ['/reconnect-le-coffre-fort-numerique', MemberFixture::MEMBER_MAIL];
        yield 'Should be able to login on Home page as Admin' => ['/', AdminFixture::ADMIN_MAIL];
        yield 'Should be able to login on Vault page as Admin' => ['/reconnect-le-coffre-fort-numerique', AdminFixture::ADMIN_MAIL];
    }
}
