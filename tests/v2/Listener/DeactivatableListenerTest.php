<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class DeactivatableListenerTest extends AuthenticatedKernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->loginUser(MemberFixture::MEMBER_MAIL);
    }

    public function testShouldSetDisabledByAndDisabledAt(): void
    {
        /** @var User $user */
        $user = UserFactory::randomOrCreate(['enabled' => true])->object();
        $user->setEnabled(false);

        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals(MemberFixture::MEMBER_MAIL, $user->getDisabledBy()?->getEmail());
        $this->assertEquals((new \DateTime())->format('d/m/Y'), $user->getDisabledAt()?->format('d/m/Y'));
    }

    public function testShouldNotSetDisabledByAndDisabledAt(): void
    {
        /** @var User $user */
        $user = UserFactory::randomOrCreate(['enabled' => true])->object();
        $user->setEmail('should_not_set@mail.com');

        $this->em->persist($user);
        $this->em->flush();

        $this->assertNull($user->getDisabledBy()?->getEmail());
        $this->assertNull($user->getDisabledAt());
    }

    public function testShouldResetDisabledByAndDisabledAt(): void
    {
        /** @var User $user */
        $user = UserFactory::randomOrCreate(['enabled' => false])->object();
        $user->setEnabled(true);

        $this->em->persist($user);
        $this->em->flush();

        $this->assertNull($user->getDisabledBy()?->getEmail());
        $this->assertNull($user->getDisabledAt());
    }

    public function testShouldNotResetDisabledByAndDisabledAt(): void
    {
        $randomUser = UserFactory::findOrCreate(['email' => MemberFixture::MEMBER_MAIL])->object();
        $today = new \DateTime();
        /** @var User $user */
        $user = UserFactory::createOne(['enabled' => false, 'disabledBy' => $randomUser, 'disabledAt' => $today])->object();
        $user->setEmail('should_not_reset@mail.com');

        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals($randomUser, $user->getDisabledBy());
        $this->assertEquals($today->format('d/m/Y'), $user->getDisabledAt()?->format('d/m/Y'));
    }

    public function testShouldEnableOnNewUserRelay(): void
    {
        $randomUser = UserFactory::findOrCreate(['email' => MemberFixture::MEMBER_DISABLED])->object();
        $randomRelay = RelayFactory::createOne()->object();
        self::assertFalse($randomUser->isEnabled());

        $userRelay = (new MembreCentre())->setCentre($randomRelay)->setUser($randomUser);
        $this->em->persist($userRelay);
        $this->em->flush();

        self::assertTrue($randomUser->isEnabled());
    }

    /**  @dataProvider provideTestDisableWhenRemovingRelay */
    public function testDisableWhenRemovingRelay(string $userMail, int $relayCount, bool $shouldBeEnabled): void
    {
        $randomUser = UserFactory::findOrCreate(['email' => $userMail])->object();
        self::assertTrue($randomUser->isEnabled());
        self::assertCount($relayCount, $randomUser->getCentres());

        $this->em->remove($randomUser->getUserRelays()->first());
        $this->em->flush();

        self::assertEquals($shouldBeEnabled, $randomUser->isEnabled());
    }

    public function provideTestDisableWhenRemovingRelay(): \Generator
    {
        yield 'Should disable pro user when removing unique relay' => [MemberFixture::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES, 1, false];
        yield 'Should not disable pro user when removing one relay' => [MemberFixture::MEMBER_MAIL, 2, true];
    }
}
