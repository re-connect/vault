<?php

namespace App\Security\VoterV2;

use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\Attributes\User;
use App\Entity\Interface\FolderableEntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PersonalDataVoter extends Voter
{
    public const string UPDATE = 'UPDATE';
    public const string TOGGLE_VISIBILITY = 'TOGGLE_VISIBILITY';
    public const string DELETE = 'DELETE';
    public const string DOWNLOAD = 'DOWNLOAD';

    public function __construct(private readonly AuthorizationCheckerInterface $checker)
    {
    }

    /**
     * @param object $subject
     */
    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::UPDATE, self::TOGGLE_VISIBILITY, self::DELETE, self::DOWNLOAD])
            && ($subject instanceof DonneePersonnelle);
    }

    /**
     * @param DonneePersonnelle $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::UPDATE => $this->canUpdate($user, $subject),
            self::TOGGLE_VISIBILITY => $this->canToggleVisibility($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            self::DOWNLOAD => $this->canDownload($user, $subject),
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
        if ($subject instanceof FolderableEntityInterface && !$subject->canToggleVisibility()) {
            return false;
        }

        return $this->canUpdate($user, $subject);
    }

    private function canDelete(User $user, DonneePersonnelle $subject): bool
    {
        if ($subject instanceof FolderableEntityInterface) {
            return $user->isBeneficiaire() && $this->canUpdate($user, $subject);
        }

        return $this->canUpdate($user, $subject);
    }

    private function canDownload(User $user, DonneePersonnelle $subject): bool
    {
        if (!$subject instanceof FolderableEntityInterface) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->canUpdate($user, $subject);
    }
}
