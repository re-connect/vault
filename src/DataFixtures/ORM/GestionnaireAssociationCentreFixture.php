<?php

namespace App\DataFixtures\ORM;

use App\Entity\Attributes\Client;
use App\Entity\ClientCentre;
use App\Manager\FixtureManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GestionnaireAssociationCentreFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private ?ObjectManager $manager = null;

    public function __construct(private readonly FixtureManager $fixtureManager)
    {
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [TypeCentreFixture::class];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->createCentersForClient('applimobile');
        $this->createCentersForClient('rosalie');
        $this->createCentersForClient('reconnect_pro');

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
    #[\Override]
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
