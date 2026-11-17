<?php

namespace App\Tests\v2\Manager\UserManager;

use App\Entity\User;
use App\ManagerV2\UserManager;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UpdatePasswordTest extends KernelTestCase
{
    use Factories;

    private UserManager $userManager;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->userManager = $container->get(UserManager::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    /**
     * @dataProvider provideTestUpdatePasswordClearsTemporaryPassword
     */
    public function testUpdatePasswordClearsTemporaryPassword(bool $hasTemporaryPassword): void
    {
        $user = UserFactory::createOne(['hasTemporaryPassword' => $hasTemporaryPassword]);
        $userId = $user->getId();

        $this->userManager->updatePassword($user, 'OwnChosenPassword42!');
        $this->em->clear();

        $stillHasTemporaryPassword = $this->em->find(User::class, $userId)->hasTemporaryPassword();
        self::assertFalse($stillHasTemporaryPassword);
    }

    public static function provideTestUpdatePasswordClearsTemporaryPassword(): \Generator
    {
        yield 'Should clear temporary password flag when user has a temporary password' => [true];
        yield 'Should keep temporary password flag cleared when user has no temporary password' => [false];
    }
}
