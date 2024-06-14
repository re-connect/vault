<?php

namespace App\DataFixtures\v2;

use App\Tests\Factory\RelayFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class RelayFixture extends Fixture implements FixtureGroupInterface
{
    public const DEFAULT_PRO_RELAY = 'Centre test pro';
    public const DEFAULT_BENEFICIARY_RELAY = 'Centre test beneficiaire';
    public const SHARED_PRO_BENEFICIARY_RELAY_1 = 'Centre test pro + beneficiaire 1';
    public const SHARED_PRO_BENEFICIARY_RELAY_2 = 'Centre test pro + beneficiaire 2';
    public const SHARED_PRO_PRO_RELAY_1 = 'Centre test pro + pro 1';
    public const SHARED_PRO_PRO_RELAY_2 = 'Centre test pro + pro 2';

    #[\Override]
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
        ]);
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
