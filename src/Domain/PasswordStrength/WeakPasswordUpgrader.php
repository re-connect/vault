<?php

namespace App\Domain\PasswordStrength;

use App\Entity\Attributes\User;
use App\ManagerV2\UserManager;
use App\ServiceV2\Helper\PasswordHelper;
use Doctrine\ORM\EntityManagerInterface;

readonly class WeakPasswordUpgrader
{
    public function __construct(
        private UserManager $userManager,
        private PasswordHelper $passwordHelper,
        private EntityManagerInterface $em,
    ) {
    }

    public function markPasswordCompliant(User $user, string $password): void
    {
        if (!$user->hasPasswordWithLatestPolicy() && $this->passwordHelper->isStrongPassword($password)) {
            $user->setHasPasswordWithLatestPolicy(true);
            $this->em->flush();
        }
    }

    public function checkUpdateWeakPassword(User $user, mixed $newPassword): void
    {
        if ($newPassword && $this->passwordHelper->isStrongPassword($newPassword)) {
            $this->userManager->updatePassword($user, $newPassword);
        }
    }
}
