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

    public function testAddDocument()
    {
        $beneficiaire = $this->createBeneficary();
        $crawler = $this->client->request('GET', $this->router->generate('re_app_document_list', ['id' => $beneficiaire->getId()]));

        // Document page
        $crawler = $this->client->click($crawler->filter('a:contains("'.$this->translator->trans('beneficiaire.menu.mesDocuments').'")')->eq(0)->link());
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertTrue($crawler->filter('html:contains("'.$this->translator->trans('document.metaTitle').'")')->count() > 0);

        // Add document
        $crawler = $this->client->click($crawler->filter('a:contains("'.$this->translator->trans('document.deposerDesFichiers').'")')->eq(0)->link());
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertTrue($crawler->filter('html:contains("'.$this->translator->trans('document.metaTitle').'")')->count() > 0);

        $beneficiaire = $this->client->getContainer()->get('security.token_storage')->getToken()->getUser()->getSubject();
        self::assertCount(0, $beneficiaire->getDocuments());
    }

    private function createBeneficary(): Beneficiaire
    {
        $beneficiaire = $this->fixtureManager->getNewRandomBeneficiaire($this->userManager);
        $beneficiaire->getUser();

        $this->em->persist($beneficiaire);
        $this->em->flush();

        $this->authenticateUser($beneficiaire->getUser()->getUsername());

        return $beneficiaire;
    }

    public function testNotes()
    {
        $beneficiaire = $this->createBeneficary();
        $crawler = $this->client->request('GET', $this->router->generate('re_app_note_list', ['id' => $beneficiaire->getId()]));

        // Note page
        $crawler = $this->client->click($crawler->filter('a:contains("'.$this->translator->trans('beneficiaire.menu.mesNotes').'")')->eq(0)->link());
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertTrue($crawler->filter('html:contains("'.$this->translator->trans('note.metaTitle').'")')->count() > 0);
        // Add Notes
        $this->client->click($crawler->filter('a:contains("'.$this->translator->trans('donneePersonnelle.ajouter').'")')->eq(0)->link());
        $this->assertResponseStatusCodeSame(200);
    }

    public function authenticateUser($username)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        $this->client->loginUser($user);
    }
}
