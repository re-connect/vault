<?php

namespace App\Tests\v2\API\v2\Oauth;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

class TemporaryPasswordTokenTest extends AbstractApiV2Test
{
    private const string NEW_PASSWORD = 'OwnChosenPassword42!';

    public function testAsksForANewPassword(): void
    {
        $content = $this->requestToken(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD);

        self::assertSame(['login' => 'success', 'weak_password' => true], $content);
    }

    public function testIssuesTokenOnceNewPasswordIsSet(): void
    {
        $content = $this->requestToken(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD, self::NEW_PASSWORD);

        self::assertArrayHasKey('access_token', $content);
        self::assertFalse($this->hasTemporaryPassword(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD));
    }

    public function testCanNotKeepTemporaryPassword(): void
    {
        $content = $this->requestToken(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD, UserFactory::STRONG_PASSWORD_CLEAR);

        self::assertSame(['login' => 'success', 'weak_password' => true], $content);
        self::assertTrue($this->hasTemporaryPassword(BeneficiaryFixture::BENEFICIARY_TEMPORARY_PASSWORD));
    }

    public function testIssuesTokenWithoutTemporaryPassword(): void
    {
        $content = $this->requestToken(BeneficiaryFixture::BENEFICIARY_MAIL);

        self::assertArrayHasKey('access_token', $content);
    }

    /**
     * Logs in like the mobile app, which sends the new password in the query string.
     *
     * @return array<string, mixed>
     */
    private function requestToken(string $email, ?string $newPassword = null): array
    {
        $client = static::createClient();
        $apiClient = $this->em->getRepository(Client::class)->findOneBy(['name' => 'applimobile']);
        $query = $newPassword ? '?'.http_build_query(['_new_password' => $newPassword]) : '';

        $client->request('GET', '/oauth/v2/token'.$query, ['json' => [
            'grant_type' => 'password',
            'client_id' => $apiClient->getIdentifier(),
            'client_secret' => $apiClient->getSecret(),
            'username' => UserFactory::findByEmail($email)->getUsername(),
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]]);
        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function hasTemporaryPassword(string $email): bool
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->getRepository(User::class)->findOneBy(['email' => $email])->hasTemporaryPassword();
    }
}
