<?php

namespace App\DataFixtures\v2;

use App\Entity\ClientBeneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientBeneficiaryFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $client = ClientFactory::find(['nom' => 'applimobile'])->object();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->object();
        $mobileExternalLink = (new ClientBeneficiaire($client, $beneficiary->getId()))->setEntity($beneficiary);
        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->object();
        $reconnectProExternalLink = (new ClientBeneficiaire($client, $beneficiary->getId()))->setEntity($beneficiary);
        $manager->persist($mobileExternalLink);
        $manager->persist($reconnectProExternalLink);
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
        return [BeneficiaryFixture::class, ClientFixture::class];
    }
}
