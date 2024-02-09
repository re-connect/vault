<?php

namespace App\Tests\v2\EventSubscriber;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\EventSubscriber\PasswordPolicySubscriber;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Zenstruck\Foundry\Test\Factories;

class PasswordPolicySubscriberTest extends WebTestCase
{
    use Factories;
    private const IMPROVE_PASSWORD_URL = '/improve-password';
    private const RANDOM_URLS = ['/beneficiaries', '/beneficiary', '/user/settings'];

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(RequestEvent::class, PasswordPolicySubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider provideTestRedirectsToImprovePasswordPage
     */
    public function testRedirectsToImprovePasswordPage(string $email, string $redirection = null): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $user = UserFactory::findByEmail($email)->object();
        $client->loginUser($user);
        $client->request('GET', '/user/redirect-user/');

        self::assertResponseRedirects($redirection);

        if (self::IMPROVE_PASSWORD_URL === $redirection) {
            self::assertFalse($user->hasPasswordWithLatestPolicy());

            // Redirects for any other page
            foreach (self::RANDOM_URLS as $url) {
                $client->request('GET', $url);
                self::assertResponseRedirects(self::IMPROVE_PASSWORD_URL);
            }
        }
    }

    public function provideTestRedirectsToImprovePasswordPage(): \Generator
    {
        yield 'Should redirect for beneficiary user with weak password' => [BeneficiaryFixture::BENEFICIARY_PASSWORD_WEAK, self::IMPROVE_PASSWORD_URL];
        yield 'Should not redirect for beneficiary user with strong password' => [BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary'];
        yield 'Should redirect for pro user with weak password' => [MemberFixture::MEMBER_PASSWORD_WEAK, self::IMPROVE_PASSWORD_URL];
        yield 'Should not redirect for pro user with strong password' => [MemberFixture::MEMBER_MAIL, '/beneficiaries'];
    }
}
