<?php

namespace App\ServiceV2\Traits;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

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
}
