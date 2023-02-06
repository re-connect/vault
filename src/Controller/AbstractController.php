<?php

namespace App\Controller;

use App\Entity\User;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    protected function getUser(): ?User
    {
        /** @var ?User $user */
        $user = parent::getUser();

        return $user;
    }
}
