<?php

namespace App\Tests\v2\Entity;

use App\Entity\Centre;
use App\Entity\MembreCentre;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{
    public function testCreateProUserRelay(): void
    {
        $userRelay = User::createUserRelay(new User(), new Centre());

        self::assertEquals([
            MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true,
            MembreCentre::TYPEDROIT_GESTION_MEMBRES => false,
        ], $userRelay->getDroits());
    }
}
