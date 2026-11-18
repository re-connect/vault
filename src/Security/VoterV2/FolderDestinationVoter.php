<?php

namespace App\Security\VoterV2;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class FolderDestinationVoter extends Voter
{
    public const string WRITE_INTO = 'WRITE_INTO_FOLDER';

    public function __construct(private readonly AuthorizationCheckerInterface $checker)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::WRITE_INTO === $attribute && $subject instanceof FolderDestination;
    }

    /** @param FolderDestination $subject */
    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        $beneficiary = $subject->beneficiary;

        return null !== $beneficiary
            && $subject->folder->getBeneficiaire() === $beneficiary
            && $this->checker->isGranted(PersonalDataVoter::UPDATE, $subject->folder);
    }
}
