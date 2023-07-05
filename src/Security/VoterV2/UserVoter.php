<?php

namespace App\Security\VoterV2;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const DELETE = 'DELETE';
    public const UPDATE = 'UPDATE';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @param object $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::DELETE, self::UPDATE])
            && $subject instanceof User;
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::DELETE => $this->canDelete($user, $subject),
            self::UPDATE => $this->canUpdate($user, $subject),
            default => false,
        };
    }

    private function canDelete(User $user, User $subject): bool
    {
        return $user === $subject && $user->isBeneficiaire();
    }

    private function canUpdate(User $user, User $subject): bool
    {
        if ($user->isBeneficiaire()) {
            return $user === $subject;
        }

        if ($user->isMembre()) {
            return match ($subject->getTypeUser()) {
                User::USER_TYPE_MEMBRE => $this->authorizationChecker->isGranted('UPDATE', $subject->getSubjectMembre()),
                User::USER_TYPE_BENEFICIAIRE => $this->authorizationChecker->isGranted('UPDATE', $subject->getSubjectBeneficiaire()),
                default => false,
            };
        }

        return false;
    }
}
