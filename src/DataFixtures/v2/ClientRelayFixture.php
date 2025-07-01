<?php

namespace App\DataFixtures\v2;

use App\Entity\Attributes\ClientCentre;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\RelayFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientRelayFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        $relay = RelayFactory::find(['nom' => RelayFixture::DEFAULT_BENEFICIARY_RELAY]);
        $clientRelay = new ClientCentre($client, 42);
        $relay->addExternalLink($clientRelay);
        $manager->persist($clientRelay);
        $manager->flush();
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        $relay = RelayFactory::find(['nom' => RelayFixture::DEFAULT_PRO_RELAY]);
        $clientRelay = new ClientCentre($client, 43);
        $relay->addExternalLink($clientRelay);
        $manager->persist($clientRelay);
        $manager->flush();
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    #[\Override]
    public function getDependencies(): array
    {
        return [BeneficiaryFixture::class, ClientFixture::class];
    }
}
