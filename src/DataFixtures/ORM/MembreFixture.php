<?php

namespace App\DataFixtures\ORM;

use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\ClientMembre;
use App\Entity\MembreCentre;
use App\Manager\FixtureManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MembreFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private FixtureManager $fixtureManager;
    private ?ObjectManager $manager;

    public function __construct(FixtureManager $fixtureManager)
    {
        $this->fixtureManager = $fixtureManager;
    }

    public function getDependencies(): array
    {
        return [GestionnaireAssociationCentreFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->createMembersForClient('rosalie');
        $this->createMembersForClient('axel');
        $this->createMembersForClient('applimobile');

        $manager->flush();
    }

    private function createMembersForClient(string $clientName): void
    {
        $permissions = [
            MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true,
            MembreCentre::TYPEDROIT_GESTION_MEMBRES => true,
        ];
        $client = $this->manager->getRepository(Client::class)->findOneBy(['nom' => $clientName]);
        $centers = $this->manager->getRepository(Centre::class)->findByClientIdentifier($client->getRandomId());
        /** @var Centre $center */
        $center = $centers[0];

        for ($i = 1; $i <= 5; ++$i) {
            $username = 1 === $i ? 'dupond.henry'.$center->getId() : null;
            $membre = $this->fixtureManager->getNewRandomMembre($username);
            $this->manager->persist($membre);
            $this->manager->flush();

            $membreCentre = (new MembreCentre())->setCentre($center)->setBValid(true)->setDroits($permissions);
            $this->manager->persist($membreCentre);
            $membre->addMembresCentre($membreCentre);
            $externalLink = (new ClientMembre($client, $membre->getId()));
            $this->manager->persist($externalLink);
            $membre->addExternalLink($externalLink);
        }
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
