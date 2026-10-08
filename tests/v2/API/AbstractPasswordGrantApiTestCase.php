<?php

namespace App\Tests\v2\API;

use ApiPlatform\Symfony\Bundle\Test\Client as ApiPlatformClient;
use App\Entity\Membre;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

abstract class AbstractPasswordGrantApiTestCase extends AbstractApiTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function loginAsMember(ApiPlatformClient $client, $clientName = 'applimobile', $grantType = 'password'): Membre
    {
        $apiClient = $this->em->getRepository(Client::class)->findOneBy(['name' => $clientName]);
        $member = $this->em->getRepository(Membre::class)->findByClientIdentifier($apiClient->getIdentifier())[0];

        $this->requestAccessToken($client, $apiClient, $member->getUser()->getUsername(), $grantType);

        return $member;
    }

    public function loginAsUser(ApiPlatformClient $client, string $email): void
    {
        $apiClient = $this->em->getRepository(Client::class)->findOneBy(['name' => 'applimobile']);

        $this->requestAccessToken($client, $apiClient, UserFactory::findByEmail($email)->getUsername(), 'password');
    }

    private function requestAccessToken(ApiPlatformClient $client, Client $apiClient, string $username, string $grantType): void
    {
        $client->request('GET', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $apiClient->getIdentifier(),
            'client_secret' => $apiClient->getSecret(),
            'username' => $username,
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]]);

        $this->assertResponseStatusCodeSame(200);

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->accessToken = $content['access_token'];
    }
}
