<?php

namespace App\DataFixtures\v2;

use App\Entity\Attributes\User;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class AdminFixture extends Fixture implements FixtureGroupInterface
{
    public const ADMIN_MAIL = 'admin@mail.com';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne(['email' => self::ADMIN_MAIL, 'roles' => ['ROLE_ADMIN', 'ROLE_SONATA_ADMIN'], 'firstVisit' => false, 'typeUser' => User::USER_TYPE_ADMINISTRATEUR]);
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
