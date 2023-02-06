<?php

namespace App\Tests\v1\Controller;

use App\Entity\User;
use App\Manager\FixtureManager;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MainControllerTest extends WebTestCase
{
    private ?UserManager $userManager;
    private ?FixtureManager $fixtureManager;
    private ?EntityManagerInterface $em;
    private ?UserPasswordHasherInterface $hasher;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = self::getContainer();

        $this->userManager = $container->get(UserManager::class);
        $this->fixtureManager = $container->get(FixtureManager::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);
    }

    public function testcontactV2()
    {
        $this->client->request('GET', '/nous-contacter');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request('GET', '/nous-contacter');
        $this->assertTrue($crawler->filter('html:contains("'.$this->client->getContainer()->get('translator')->trans('contact_page_title').'")')->count() > 0);
    }

    public function testCreateTestUser()
    {
        try {
            $beneficiaire = $this->fixtureManager->getNewRandomBeneficiaire($this->userManager);
            $beneficiaire->getUser()->setTest(true);
            $beneficiaire->getUser()->setRoles(['ROLE_ADMIN']);
            $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
            $em->persist($beneficiaire);
            $em->flush();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
        self::assertTrue($this->testPasswordAndAuthenticate($beneficiaire->getUser()->getUsername(), 'password'));

        return $beneficiaire;
    }

    public function testHomeV2()
    {
        $beneficiaire = $this->testCreateTestUser();

        $crawler = $this->client->request('GET', '/');
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form')->eq(0)->form();
        $form->setValues([
            '_username' => $beneficiaire->getUser()->getUsername(),
            '_password' => 'password',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->request('GET', '/admin/dashboard');
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPageVault()
    {
        $this->client->request('GET', '/reconnect-le-coffre-fort-numerique');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request('GET', '/reconnect-le-coffre-fort-numerique');

        $this->assertTrue($crawler->filter('html:contains("'.$this->client->getContainer()->get('translator')->trans('cfn_page_hero_text').'")')->count() > 0);

        $form = $crawler->selectButton('submitLogin')->form();
        $form['_username'] = 'admin@reconnect.fr';
        $form['_password'] = 'toulon83!';
        $crawler = $this->client->submit($form);
    }

    public function testPageRP()
    {
        $this->client->request('GET', '/reconnect-la-solution-pro');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request('GET', '/reconnect-la-solution-pro');

        $this->assertTrue($crawler->filter('html:contains("'.$this->client->getContainer()->get('translator')->trans('rp_page_hero_text').'")')->count() > 0);
    }

    private function testPasswordAndAuthenticate($username, $password): bool
    {
        $query = $this->em->createQuery('SELECT u FROM '.User::class.' u WHERE u.username = :username');

        $query->setParameter('username', $username);

        $user = $query->getOneOrNullResult();

        if ($user && $this->hasher->isPasswordValid($user, $password)) {
            $this->userManager->authenticateUser($user);

            return true;
        }

        return false;
    }
}
