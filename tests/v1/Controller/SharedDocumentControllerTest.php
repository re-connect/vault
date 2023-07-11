<?php

namespace App\Tests\v1\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Manager\FixtureManager;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SharedDocumentControllerTest extends WebTestCase
{
    private ?KernelBrowser $client;
    private ?UserManager $userManager;
    private ?FixtureManager $fixtureManager;
    private ?EntityManagerInterface $em;
    private ?Beneficiaire $beneficiare;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = self::getContainer();
        $this->userManager = $container->get(UserManager::class);
        $this->fixtureManager = $container->get(FixtureManager::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->beneficiare = $this->fixtureManager->getNewRandomBeneficiaire($this->userManager);
        $this->em->persist($this->beneficiare);
        $this->em->flush();
    }

    public function testShareDocument()
    {
        $document = $this->generateDocument();
        $this->client->loginUser($this->beneficiare->getUser());
        $crawler = $this->client->request('GET', '/appli/document/'.$document->getId().'/share');
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('Confirmer')->form();
        $form['email[email]'] = 'gandalf@gmail.com';
        $this->client->submit($form);
        //        $this->assertResponseRedirects('/api/beneficiaire/'.$this->beneficiare->getId().'/document');
    }

    private function generateDocument(): Document
    {
        $document = (new Document())
            ->setNom('mon document')
            ->setTaille(500)
            ->setBeneficiaire($this->beneficiare);
        $this->em->persist($document);
        $this->em->flush();

        return $document;
    }
}
