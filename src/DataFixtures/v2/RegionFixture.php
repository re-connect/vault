<?php

namespace App\DataFixtures\v2;

use App\Tests\Factory\RegionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class RegionFixture extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        RegionFactory::createOne(['name' => 'Occitanie']);
    }

    public static function getGroups(): array
    {
        return ['v2'];
    }
}
