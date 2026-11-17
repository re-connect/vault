<?php

namespace App\Tests\v2\API\v3\PasswordGrant\User;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class MobileUserTest extends AbstractPasswordGrantApiTestCase
{
    public function testSwitchLocale(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, '/users/switch-locale', ['json' => ['locale' => 'en']]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('en', $this->findUser($user->getId())->getLastLang());
    }

    public function testRegisterNotificationToken(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, '/users/me/register-notification-token', ['json' => [
            'notification_token' => 'ExponentPushToken[test]',
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('ExponentPushToken[test]', $this->findUser($user->getId())->getFcnToken());
    }

    public function testRequestPersonalAccountData(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, Request::METHOD_GET, '/user/request-personal-account-data');

        $this->assertResponseStatusCodeSame(201);
        $this->assertEmailCount(1);
        $this->assertTrue($this->findUser($user->getId())->hasRequestedPersonalAccountData());
    }

    public function testRequestPersonalAccountDataWithTrailingSlashRedirects(): void
    {
        // The mobile app calls this route with a trailing slash
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, Request::METHOD_GET, '/user/request-personal-account-data/');

        $this->assertResponseRedirects(sprintf('http://localhost/api/v3/user/request-personal-account-data?access_token=%s', $this->accessToken), 301);
    }

    public function testMemberCanNotRequestPersonalAccountData(): void
    {
        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/user/request-personal-account-data');

        $this->assertResponseStatusCodeSame(403);
    }

    private function findUser(int $id): User
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(User::class, $id);
    }
}
