<?php

namespace App\DataFixtures\v2;

use App\Entity\Attributes\ClientBeneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientBeneficiaryFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $clients = ClientFactory::all();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->object();

        foreach ($clients as $client) {
            if ('reconnect_pro' === $client->getNom()) {
                continue;
            }
            $externalLink = (new ClientBeneficiaire($client->object(), $beneficiary->getId()))->setEntity($beneficiary);
            $manager->persist($externalLink);
        }

        $client = ClientFactory::find(['nom' => 'reconnect_pro'])->object();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_RP_LINK)->object();
        $reconnectProExternalLink = (new ClientBeneficiaire($client, $beneficiary->getId()))->setEntity($beneficiary)->setBeneficiaireCentre($beneficiary->getBeneficiairesCentres()->first() ?: null);
        $manager->persist($reconnectProExternalLink);
        $manager->flush();
    }

    /** @return string[]     */
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
