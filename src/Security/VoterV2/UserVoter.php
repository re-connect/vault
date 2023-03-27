<?php

namespace App\Security\VoterV2;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const SELF_EDIT = 'SELF_EDIT';
    public const DELETE_BENEFICIARY = 'DELETE_BENEFICIARY';

    /**
     * @param object $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::SELF_EDIT, self::DELETE_BENEFICIARY])
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
            self::SELF_EDIT => $this->canSelfEdit($user, $subject),
            self::DELETE_BENEFICIARY => $this->canDeleteBeneficiary($user, $subject),
            default => false,
        };
    }

    private function canSelfEdit(User $user, User $subject): bool
    {
        return $user->isValidUser() && $user === $subject;
    }

    private function canDeleteBeneficiary(User $user, User $subject): bool
    {
        return $this->canSelfEdit($user, $subject) && $user->isBeneficiaire();
    }
}
