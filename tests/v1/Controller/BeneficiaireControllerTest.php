<?php

namespace App\Tests\v1\Controller;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Manager\FixtureManager;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BeneficiaireControllerTest extends WebTestCase
{
    private ?UserManager $userManager;
    private ?FixtureManager $fixtureManager;
    private ?EntityManagerInterface $em;
    private ?KernelBrowser $client;
    private ?TranslatorInterface $translator;
    private ?Router $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $container = self::getContainer();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtureManager = $this->client->getContainer()->get(FixtureManager::class);
        $this->userManager = $this->client->getContainer()->get(UserManager::class);
        $this->router = $container->get('router');
        $this->translator = $container->get('translator');
    }

    protected static function ensureKernelShutdown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }

    private function createBeneficary(): Beneficiaire
    {
        $beneficiaire = $this->fixtureManager->getNewRandomBeneficiaire($this->userManager);
        $beneficiaire->getUser()->setTest(true);

        $this->em->persist($beneficiaire);
        $this->em->flush();

        $this->authenticateUser($beneficiaire->getUser()->getUsername());

        return $beneficiaire;
    }

    public function authenticateUser($username)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        $this->client->loginUser($user);
    }
}
