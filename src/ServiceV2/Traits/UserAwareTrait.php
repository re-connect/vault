<?php

namespace App\ServiceV2\Traits;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

trait UserAwareTrait
{
    private Security $security;

    protected function getUser(): ?User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    protected function isLoggedInUser(User $user): bool
    {
        return $this->getUser() === $user;
    }
}
