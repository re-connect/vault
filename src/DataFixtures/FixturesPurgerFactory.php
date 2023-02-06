<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

class FixturesPurgerFactory implements PurgerFactory
{
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false): PurgerInterface
    {
        return (new FixturesPurger())->setEntityManager($em);
    }
}
