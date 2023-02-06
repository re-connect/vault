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
    public const RELAY_NAME = 'Centre test';

    public function load(ObjectManager $manager)
    {
        RelayFactory::createOne([
            'nom' => self::RELAY_NAME,
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
