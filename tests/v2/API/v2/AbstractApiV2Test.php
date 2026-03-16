<?php

namespace App\Tests\v2\API\v2;

use ApiPlatform\Symfony\Bundle\Test\Client as ApiPlatformClient;
use App\Entity\Membre;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v3\AbstractApiTest;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

abstract class AbstractApiV2Test extends AbstractApiTest
{
    protected const BASE_URL = '/api/v2';

    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function generateUrl(string $url): string
    {
        return sprintf('%s%s?access_token=%s', self::BASE_URL, $url, $this->accessToken);
    }

    public function loginAsMember(ApiPlatformClient $client, $clientName = 'applimobile', $grantType = 'password'): Membre
    {
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        /** @var Client $client */
        $apiClient = $em->getRepository(Client::class)->findOneBy(['name' => $clientName]);
        $member = $em->getRepository(Membre::class)->findByClientIdentifier($apiClient->getIdentifier())[0];

        $client->request('GET', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $apiClient->getIdentifier(),
            'client_secret' => $apiClient->getSecret(),
            'username' => $member->getUser()->getUsername(),
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]]);

        $this->assertResponseStatusCodeSame(200);

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->accessToken = $content['access_token'];

        return $member;
    }
}
