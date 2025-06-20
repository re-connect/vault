<?php

namespace App\Tests\v2\Manager\RelayManager;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\ManagerV2\RelayManager;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RemoveUserFromRelayTest extends KernelTestCase
{
    private RelayManager $relayManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->relayManager = self::getContainer()->get(RelayManager::class);
    }

    public function testRemoveUserFromRelay(): void
    {
        // Given a user and a relay
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => MemberFixture::MEMBER_MAIL]);
        $relayCount = $user->getRelays()->count();
        $this->assertNotNull($relayCount);
        $relay = $user->getRelays()->first();
        $this->assertNotNull($relay);

        // When removing user from relay
        $this->relayManager->removeUserFromRelay($user, $relay);

        // User should not be affiliated to relay
        $this->assertEquals($relayCount - 1, $user->getRelays()->count());
        $this->assertNotContains($relay, $user->getRelays());
        $this->assertTrue($user->isEnabled());
    }

    public function testRemoveUserFromLastRelay(): void
    {
        // Given a pro user and a relay
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => MemberFixture::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES]);
        $this->assertTrue($user->isEnabled());
        $relayCount = $user->getRelays()->count();
        $this->assertEquals(1, $relayCount);
        $relay = $user->getRelays()->first();
        $this->assertNotNull($relay);
        // When removing user from his last relay
        $this->relayManager->removeUserFromRelay($user, $relay);

        // User should not be affiliated to relay and be disaffiliated
        $this->assertEquals($relayCount - 1, $user->getRelays()->count());
        $this->assertNotContains($relay, $user->getRelays());
        $this->assertFalse($user->isEnabled());
    }
}
