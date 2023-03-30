<?php

namespace App\ControllerV2;

use App\Entity\Membre;
use App\Entity\User;

class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function getUser(): ?User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }

    public function getProfessional(): ?Membre
    {
        return $this->getUser()?->getSubjectMembre();
    }

    protected function isLoggedInUser(User $user): bool
    {
        return $this->getUser() === $user;
    }
}
