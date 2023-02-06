<?php

namespace App\DataFixtures\v2;

use App\Entity\User;
use App\Tests\Factory\GestionnaireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class GestionnaireFixture extends Fixture implements FixtureGroupInterface
{
    public const GESTIONNAIRE_MAIL = 'v2_test_user_gestionnaire@mail.com';

    public function load(ObjectManager $manager)
    {
        $this->createGestionnaire($this->getTestUser());
    }

    public function getTestUser(): User
    {
        return UserFactory::createOne(['email' => self::GESTIONNAIRE_MAIL])->object();
    }

    public function createGestionnaire(User $user): void
    {
        GestionnaireFactory::createOne([
            'user' => $user,
        ]);
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
