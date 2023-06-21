<?php

namespace App\Security\VoterV2;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const SELF_DELETE = 'SELF_DELETE';

    /**
     * @param object $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::SELF_DELETE])
            && $subject instanceof User;
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || $user !== $subject) {
            return false;
        }

        return match ($attribute) {
            self::SELF_DELETE => $this->canSelfDelete($user, $subject),
            default => false,
        };
    }

    private function canSelfDelete(User $user, User $subject): bool
    {
        return $user === $subject && $user->isBeneficiaire();
    }
}
