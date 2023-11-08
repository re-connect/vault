<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class ProDefaultPermissionListenerTest extends AuthenticatedKernelTestCase
{
    private EntityManagerInterface $em;
    use Factories;

    protected function setUp(): void
    {
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function provideTestProDefaultPermissionListener(): ?\Generator
    {
        yield 'Should give beneficiary management permission when created by authorized user' => [true];
        yield 'Should not give beneficiary management permission when created by unauthorized user' => [false];
    }

    /** @dataProvider provideTestProDefaultPermissionListener */
    public function testProDefaultPermission(bool $hasPermission): void
    {
        $loggedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $this->loginUser(MemberFixture::MEMBER_MAIL_WITH_RELAYS);

        $relay = $loggedUser->getCentres()->first();
        $relayLink = $loggedUser->getUserRelay($relay);

        if (!$hasPermission) {
            $relayLink->togglePermission(MembreCentre::MANAGE_BENEFICIARIES_PERMISSION);
        }

        self::assertEquals($hasPermission, $relayLink->getDroits()[MembreCentre::MANAGE_BENEFICIARIES_PERMISSION]);

        $proUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION)->object();
        self::assertEmpty($proUser->getCentres());

        $userRelay = User::createUserRelay($proUser, $relay);
        $this->em->persist($userRelay);
        $this->em->flush();

        self::assertEquals([
            MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => $hasPermission,
            MembreCentre::MANAGE_PROS_PERMISSION => false,
        ], $userRelay->getDroits());
    }
}
