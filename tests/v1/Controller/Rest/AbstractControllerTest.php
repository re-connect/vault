<?php

namespace App\Tests\v1\Controller\Rest;

use App\Entity\Membre;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractControllerTest extends WebTestCase
{
    use Factories;
    protected AbstractBrowser $client;
    protected ?string $accessToken = null;
    protected string $baseUrl = '/api/v2/';
    private ?EntityManagerInterface $em;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function generateUrl($url): string
    {
        return $this->baseUrl.$url.'?access_token='.$this->accessToken;
    }

    public function truncateTable(string $entityName)
    {
        $this->em->createQueryBuilder()
            ->delete($entityName)
            ->getQuery()
            ->execute();
    }

    public function truncateTables(array $entityNames)
    {
        foreach ($entityNames as $entityName) {
            $this->truncateTable($entityName);
        }
    }

    public function loginAsClient(string $clientName, string $grantType = 'client_credentials'): Client
    {
        /** @var Client $client */
        $client = $this->em->getRepository(Client::class)->findOneBy(['name' => $clientName]);

        $this->client->request('GET', '/oauth/v2/token', [
            'grant_type' => $grantType,
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->accessToken = $content['access_token'];

        return $client;
    }

    public function loginAsMember($clientName = 'applimobile', $grantType = 'password'): Membre
    {
        /** @var Client $client */
        $client = $this->em->getRepository(Client::class)->findOneBy(['name' => $clientName]);
        $member = $this->em->getRepository(Membre::class)->findByClientIdentifier($client->getIdentifier())[0];

        $this->client->request('GET', '/oauth/v2/token', [
            'grant_type' => $grantType,
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
            'username' => $member->getUser()->getUsername(),
            'password' => 'Password1',
        ]);

        $this->assertResponseStatusCodeSame(200);

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->accessToken = $content['access_token'];

        return $member;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
