<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans('user_error_disabled_account'));
        }
    }
}
