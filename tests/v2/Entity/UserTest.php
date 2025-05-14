<?php

namespace App\Tests\v2\Entity;

use App\Entity\Attributes\Centre;
use App\Entity\Attributes\MembreCentre;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{
    public function testCreateProUserRelay(): void
    {
        $userRelay = User::createUserRelay(new User(), new Centre());

        self::assertEquals([
            MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => false,
            MembreCentre::MANAGE_PROS_PERMISSION => false,
        ], $userRelay->getDroits());
    }
}
