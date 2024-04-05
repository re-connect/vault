<?php

namespace App\DataFixtures\v2;

use App\Entity\ClientMembre;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\MembreFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientMemberFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $client = ClientFactory::find(['nom' => 'applimobile'])->object();
        $membre = MembreFactory::findByEmail(MemberFixture::MEMBER_WITH_CLIENT)->object();
        $externalLink = (new ClientMembre($client, $membre->getId()))->setEntity($membre);
        $manager->persist($externalLink);
        $manager->flush();
    }

    /** @return string[]     */
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [MemberFixture::class, ClientFixture::class];
    }
}
