<?php

namespace App\Tests\v2\EventSubscriber;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\EventSubscriber\StrongPasswordSubscriber;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Zenstruck\Foundry\Test\Factories;

class StrongPasswordSubscriberTest extends WebTestCase
{
    use Factories;
    private const RANDOM_URLS = [];

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(RequestEvent::class, StrongPasswordSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider provideTestRedirectsToImprovePasswordPage
     */
    public function testRedirectsToImprovePasswordPage(string $email): void
    {
        self::ensureKernelShutdown();
        $user = UserFactory::findByEmail($email)->object();

        self::assertNull($user->hasUpdatedPasswordWithLatestPolicy());

        $client = static::createClient();
        $client->loginUser($user);

        // Redirect after login
        self::assertResponseRedirects('/improve-password');

        // Redirects for any other page
        foreach (self::RANDOM_URLS as $url) {
            $client->request('GET', $url);
            self::assertResponseRedirects('/improve-password');
        }
    }

    public function provideTestRedirectsToImprovePasswordPage(): \Generator
    {
        yield 'Should redirect for beneficiary user' => [BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should redirect for beneficiary user' => [MemberFixture::MEMBER_MAIL];
    }
}
