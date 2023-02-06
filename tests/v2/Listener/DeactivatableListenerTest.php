<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
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
}
