<?php

namespace App\DataFixtures\v2;

use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RelayFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const DEFAULT_PRO_RELAY = 'Centre test pro';
    public const DEFAULT_BENEFICIARY_RELAY = 'Centre test beneficiaire';
    public const SHARED_PRO_BENEFICIARY_RELAY_1 = 'Centre test pro + beneficiaire 1';
    public const SHARED_PRO_BENEFICIARY_RELAY_2 = 'Centre test pro + beneficiaire 2';
    public const SHARED_PRO_PRO_RELAY_1 = 'Centre test pro + pro 1';
    public const SHARED_PRO_PRO_RELAY_2 = 'Centre test pro + pro 2';

    public function load(ObjectManager $manager)
    {
        $this->createTestRelay(self::DEFAULT_PRO_RELAY);
        $this->createTestRelay(self::DEFAULT_BENEFICIARY_RELAY);
        $this->createTestRelay(self::SHARED_PRO_BENEFICIARY_RELAY_1);
        $this->createTestRelay(self::SHARED_PRO_BENEFICIARY_RELAY_2);
        $this->createTestRelay(self::SHARED_PRO_PRO_RELAY_1);
        $this->createTestRelay(self::SHARED_PRO_PRO_RELAY_2);
    }

    private function createTestRelay(string $name): void
    {
        RelayFactory::createOne([
            'nom' => $name,
            'gestionnaire' => UserFactory::findOrCreate(['email' => GestionnaireFixture::GESTIONNAIRE_MAIL])->getSubjectGestionnaire(),
        ]);
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [GestionnaireFixture::class];
    }
}
