<?php

namespace App\DataFixtures\ORM;

use App\Entity\Client;
use App\Entity\ClientCentre;
use App\Manager\FixtureManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GestionnaireAssociationCentreFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private FixtureManager $fixtureManager;
    private ?ObjectManager $manager;

    public function __construct(FixtureManager $fixtureManager)
    {
        $this->fixtureManager = $fixtureManager;
    }

    public function getDependencies(): array
    {
        return [TypeCentreFixture::class];
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->createCentersForClient('applimobile');
        $this->createCentersForClient('rosalie');
        $this->createCentersForClient('axel');

        $manager->flush();
    }

    private function createCentersForClient(string $clientName): void
    {
        $client = $this->manager->getRepository(Client::class)->findOneBy(['nom' => $clientName]);

        for ($i = 1; $i <= 7; ++$i) {
            $gestionnaire = $this->fixtureManager->getNewRandomGestionnaire();
            $association = $this->fixtureManager->getNewRandomAssociation();
            $gestionnaire->setAssociation($association);
            $centre = $this->fixtureManager->getNewRandomCentre();
            $gestionnaire->addCentre($centre);
            $this->manager->persist($centre);
            $this->manager->persist($gestionnaire);
            $this->manager->persist($association);
            $this->manager->flush();

            $externalLink = (new ClientCentre())->setClient($client)->setDistantId($centre->getId());
            $centre->addExternalLink($externalLink);
        }
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
