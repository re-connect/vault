<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UserChecker implements UserCheckerInterface
{
    public function __construct(private TranslatorInterface $translator)
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

        $errorMessage = $this->getErrorMessages($user);

        if ($errorMessage) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans($errorMessage));
        }
    }

    private function getErrorMessages(User $user): ?string
    {
        return match (true) {
            !$user->isEnabled() => 'login_error_disabled_account',
            $user->getSubjectBeneficiaire()?->isCreating() => 'login_error_account_in_creation',
            default => null,
        };
    }
}
