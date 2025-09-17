<?php

namespace App\Tests\v2\Manager\UserManager;

use App\Entity\Attributes\User;
use App\ManagerV2\UserManager;
use App\ServiceV2\Helper\PasswordHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratePasswordTest extends KernelTestCase
{
    private UserManager $userManager;
    private PasswordHelper $passwordHelper;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->userManager = $container->get(UserManager::class);
        $this->passwordHelper = $container->get(PasswordHelper::class);
    }

    public function testGeneratePassword(): void
    {
        $generateStrongPassword = true;
        for ($i = 0; $i < 50; ++$i) {
            if (!$this->passwordHelper->isStrongPassword(
                (new User())->addRole('ROLE_USER'),
                $this->userManager->getRandomPassword()
            )) {
                $generateStrongPassword = false;
            }
        }

        self::assertTrue($generateStrongPassword);
    }
}
