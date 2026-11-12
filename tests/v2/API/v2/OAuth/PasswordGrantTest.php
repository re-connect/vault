<?php

namespace App\Tests\v2\API\v2\OAuth;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

class PasswordGrantTest extends AbstractApiV2Test
{
    private const string OLD_USERNAME = 'old.username.oauth-test';

    /** @dataProvider provideUserWithOldUsername */
    public function testCanGetTokenWithCurrentUsernameWhenUserHasOldUsername(string $email): void
    {
        $httpClient = static::createClient();
        $this->setOldUsername($email);

        $this->loginAsUser($httpClient, $email);

        self::assertNotNull($this->accessToken);
    }

    /** @dataProvider provideUserWithOldUsername */
    public function testCannotGetTokenWithOldUsername(string $email): void
    {
        $httpClient = static::createClient();
        $this->setOldUsername($email);
        $apiClient = $this->em->getRepository(Client::class)->findOneBy(['name' => 'applimobile']);

        $httpClient->request('GET', '/oauth/v2/token', ['json' => [
            'grant_type' => 'password',
            'client_id' => $apiClient->getIdentifier(),
            'client_secret' => $apiClient->getSecret(),
            'username' => self::OLD_USERNAME,
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['error' => 'invalid_grant']);
    }

    public static function provideUserWithOldUsername(): \Generator
    {
        yield 'Member' => [MemberFixture::MEMBER_MAIL];
        yield 'Beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    private function setOldUsername(string $email): void
    {
        UserFactory::findByEmail($email)->setOldUsername(self::OLD_USERNAME);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }
}
