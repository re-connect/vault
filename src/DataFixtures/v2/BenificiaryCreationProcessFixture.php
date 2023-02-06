<?php

namespace App\DataFixtures\v2;

use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BenificiaryCreationProcessFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        BeneficiaryCreationProcessFactory::createOne(['beneficiary' => BeneficiaireFactory::randomOrCreate(), 'isCreating' => false]);
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [BeneficiaryFixture::class];
    }
}
