<?php

namespace App\Security\VoterV2;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class RelayInvitationVoter extends Voter
{
    public const string TOGGLE = 'TOGGLE_RELAY_INVITATION';

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::TOGGLE === $attribute && $subject instanceof RelayInvitation;
    }

    /** @param RelayInvitation $subject */
    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $loggedUser = $token->getUser();
        if (!$loggedUser instanceof User) {
            return false;
        }

        $user = $subject->user;
        $relay = $subject->relay;

        if ($user->hasValidLinkToRelay($relay) && !$user->getSubjectBeneficiaire()?->isCreating()) {
            return false;
        }

        if ($user->isMembre()) {
            return $this->authorizationChecker->isGranted(RelayVoter::MANAGE_PRO, $relay);
        }

        return $user->isBeneficiaire() && $loggedUser->getValidRelays()->contains($relay);
    }
}
