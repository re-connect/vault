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
    public const SHARED_PRO_BENEFICIARY_RELAY = 'Centre test pro + beneficiaire';

    public function load(ObjectManager $manager)
    {
        $this->createTestRelay(self::DEFAULT_PRO_RELAY);
        $this->createTestRelay(self::DEFAULT_BENEFICIARY_RELAY);
        $this->createTestRelay(self::SHARED_PRO_BENEFICIARY_RELAY);
    }

    private function createTestRelay(string $name): void
    {
        RelayFactory::createOne([
            'nom' => $name,
        ]);
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
