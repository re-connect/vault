<?php

namespace App\Tests\v1\Controller\Rest;

use App\DataFixtures\ORM\BeneficiaireFixture;
use App\DataFixtures\ORM\ClientFixtures;
use App\DataFixtures\ORM\GestionnaireAssociationCentreFixture;
use App\DataFixtures\ORM\MembreFixture;
use App\DataFixtures\ORM\TypeCentreFixture;
use App\Entity\AccessToken;
use App\Entity\Administrateur;
use App\Entity\Association;
use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client as OldClient;
use App\Entity\ClientCentre;
use App\Entity\ClientEntity;
use App\Entity\Contact;
use App\Entity\Creator;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\Note;
use App\Entity\RefreshToken;
use App\Entity\SharedDocument;
use App\Entity\TypeCentre;
use App\Entity\User;
use App\Manager\FixtureManager;
use App\Manager\UserManager;
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
        /*$fixturesManager = $container->get(FixtureManager::class);
        $userManager = $container->get(UserManager::class);

        $this->truncateTables([
            ClientEntity::class,
            Creator::class,
            Note::class,
            Evenement::class,
            Contact::class,
            RefreshToken::class,
            SharedDocument::class,
            Document::class,
            Dossier::class,
            MembreCentre::class,
            BeneficiaireCentre::class,
            Centre::class,
            TypeCentre::class,
            Administrateur::class,
            Gestionnaire::class,
            Association::class,
            Creator::class,
            AccessToken::class,
            OldClient::class,
            ClientCentre::class,
            Beneficiaire::class,
            Membre::class,
            User::class,
        ]);
        (new ClientFixtures())->load($this->em);
        (new TypeCentreFixture())->load($this->em);
        (new GestionnaireAssociationCentreFixture($fixturesManager))->load($this->em);
        (new BeneficiaireFixture($fixturesManager, $userManager))->load($this->em);
        (new MembreFixture($fixturesManager))->load($this->em);*/
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
            'password' => 'password',
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
