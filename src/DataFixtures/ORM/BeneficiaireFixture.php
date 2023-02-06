<?php

namespace App\DataFixtures\ORM;

use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\ClientBeneficiaire;
use App\Manager\FixtureManager;
use App\Manager\UserManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BeneficiaireFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private FixtureManager $fixtureManager;
    private UserManager $userManager;
    private ?ObjectManager $manager;

    public function __construct(FixtureManager $fixtureManager, UserManager $userManager)
    {
        $this->fixtureManager = $fixtureManager;
        $this->userManager = $userManager;
    }

    public function getDependencies(): array
    {
        return [
            ClientFixtures::class,
            GestionnaireAssociationCentreFixture::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->createBeneficiariesForClient('rosalie');
        $this->createBeneficiariesForClient('axel');
        $this->createBeneficiariesForClient('applimobile');
    }

    private function createBeneficiariesForClient(string $clientName, int $count = 5): void
    {
        $client = $this->manager->getRepository(Client::class)->findOneBy(['nom' => $clientName]);
        $centre = $this->manager->getRepository(Centre::class)->findByClientIdentifier($client->getRandomId())[0];

        for ($i = 1; $i <= $count; ++$i) {
            $beneficiaire = $this->fixtureManager->getNewRandomBeneficiaire($this->userManager);
            $this->manager->persist($beneficiaire);
            $this->manager->flush();

            $externalLink = new ClientBeneficiaire($client, $beneficiaire->getId());
            $beneficiaire->addExternalLink($externalLink);
            $this->manager->persist($externalLink);

            $beneficiairesCentre = (new BeneficiaireCentre())->setCentre($centre)->setBValid(true);
            $this->manager->persist($beneficiairesCentre);
            $externalLink->setBeneficiaireCentre($beneficiairesCentre);
            $beneficiaire->addBeneficiairesCentre($beneficiairesCentre);

            $this->manager->persist($beneficiaire);
            $this->manager->flush();
        }
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
