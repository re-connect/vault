<?php

namespace App\Tests\v2\API\v2;

use App\Entity\Attributes\Membre;
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
        $this->em = $this->client->getContainer()->get(EntityManagerInterface::class);
    }

    public function generateUrl(string $url): string
    {
        return sprintf('%s%s?access_token=%s', self::BASE_URL, $url, $this->accessToken);
    }

    public function loginAsMember($clientName = 'applimobile', $grantType = 'password'): Membre
    {
        $em = $this->client->getContainer()->get(EntityManagerInterface::class);
        /** @var Client $client */
        $client = $em->getRepository(Client::class)->findOneBy(['name' => $clientName]);
        $member = $em->getRepository(Membre::class)->findByClientIdentifier($client->getIdentifier())[0];

        $this->client->request('GET', '/oauth/v2/token', ['json' => [
            'grant_type' => $grantType,
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
            'username' => $member->getUser()->getUsername(),
            'password' => UserFactory::STRONG_PASSWORD_CLEAR,
        ]]);

        $this->assertResponseStatusCodeSame(200);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->accessToken = $content['access_token'];

        return $member;
    }
}
