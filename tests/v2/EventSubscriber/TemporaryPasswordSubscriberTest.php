<?php

namespace App\Tests\v2\EventSubscriber;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\EventSubscriber\TemporaryPasswordSubscriber;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;

class TemporaryPasswordSubscriberTest extends WebTestCase
{
    use Factories;

    private const string UPDATE_PASSWORD_URL = '/update-password';
    private const string FIRST_VISIT_URL = '/user/first-visit';
    private const array RANDOM_URLS = ['/beneficiary', '/user/settings', '/user/redirect-user/', '/user/delete'];

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(RequestEvent::class, TemporaryPasswordSubscriber::getSubscribedEvents());
    }

    public function testRedirectsToPasswordUpdate(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD));

        foreach (self::RANDOM_URLS as $url) {
            $client->request('GET', $url);
            self::assertResponseRedirects(self::UPDATE_PASSWORD_URL);
        }
    }

    public function testDoesNotRedirectWithoutTemporaryPassword(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL));

        $client->request('GET', '/beneficiary');
        self::assertResponseIsSuccessful();
    }

    public function testDoesNotRedirectFromPasswordUpdatePage(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD));

        $client->request('GET', self::UPDATE_PASSWORD_URL);
        self::assertResponseIsSuccessful();
    }

    public function testUpdatePasswordBeforeAcceptingTermsOfUse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD_FIRST_VISIT));

        $client->request('GET', self::FIRST_VISIT_URL);
        self::assertResponseRedirects(self::UPDATE_PASSWORD_URL);

        $client->request('GET', self::UPDATE_PASSWORD_URL);
        self::assertResponseIsSuccessful();
    }

    public function testDoesNotLoopWithWeakPasswordRedirection(): void
    {
        $client = static::createClient();
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD);
        $user->setHasPasswordWithLatestPolicy(false);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($user);

        $client->request('GET', '/improve-password');
        self::assertResponseIsSuccessful();
    }

    public function testDoesNotLoopWithTermsOfUseAndWeakPassword(): void
    {
        $client = static::createClient();
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD_FIRST_VISIT);
        $user->setHasPasswordWithLatestPolicy(false);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($user);
        $client->followRedirects();
        $client->setMaxRedirects(5);

        $client->request('GET', '/beneficiary');

        self::assertRouteSame('improve_password');
    }

    public function testShowsTermsOfUseOncePasswordIsUpdated(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD_FIRST_VISIT));
        $submitLabel = static::getContainer()->get(TranslatorInterface::class)->trans('submit');

        $crawler = $client->request('GET', self::UPDATE_PASSWORD_URL);
        $form = $crawler->selectButton($submitLabel)->form([
            'change_password_form[plainPassword][first]' => 'OwnChosenPassword42!',
            'change_password_form[plainPassword][second]' => 'OwnChosenPassword42!',
        ]);
        $client->followRedirects();
        $client->submit($form);

        self::assertRouteSame('user_first_visit');
    }
}
