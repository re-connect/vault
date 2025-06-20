<?php

namespace App\DataFixtures\v2;

use App\Entity\Attributes\User;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SuperAdminFixture extends Fixture implements FixtureGroupInterface
{
    public const string SUPER_ADMIN_MAIL = 'super_admin@mail.com';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne(['email' => self::SUPER_ADMIN_MAIL, 'roles' => ['ROLE_SUPER_ADMIN'], 'firstVisit' => false, 'typeUser' => User::USER_TYPE_SUPER_ADMIN]);
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
