<?php

namespace App\Security\VoterV2;

use App\Entity\DonneePersonnelle;
use App\Entity\Interface\FolderableEntityInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PersonalDataVoter extends Voter
{
    public const UPDATE = 'UPDATE';
    public const TOGGLE_VISIBILITY = 'TOGGLE_VISIBILITY';
    private AuthorizationCheckerInterface $checker;

    public function __construct(AuthorizationCheckerInterface $checker)
    {
        $this->checker = $checker;
    }

    /**
     * @param object $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::UPDATE, self::TOGGLE_VISIBILITY])
            && ($subject instanceof DonneePersonnelle);
    }

    /**
     * @param DonneePersonnelle $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::UPDATE => $this->canUpdate($user, $subject),
            self::TOGGLE_VISIBILITY => $this->canToggleVisibility($user, $subject),
            default => false,
        };
    }

    private function canUpdate(User $user, DonneePersonnelle $subject): bool
    {
        if (!$this->checker->isGranted('UPDATE', $subject->getBeneficiaire())) {
            return false;
        } elseif ($user->isMembre() && $subject->getBPrive()) {
            return false;
        }

        return true;
    }

    private function canToggleVisibility(User $user, DonneePersonnelle $subject): bool
    {
        if ($subject instanceof FolderableEntityInterface && $subject->hasParentFolder()) {
            return false;
        }

        return $this->canUpdate($user, $subject);
    }
}
