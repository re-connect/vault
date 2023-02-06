<?php

namespace App\ControllerV2;

use App\Entity\User;

class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function getUser(): ?User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }

    protected function isLoggedInUser(User $user): bool
    {
        return $this->getUser() === $user;
    }
}
