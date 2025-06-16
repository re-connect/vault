<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Membre;
use App\Entity\Attributes\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    #[\Override]
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

    protected function redirectToReferer(?string $referer): RedirectResponse
    {
        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('redirect_user');
    }
}
