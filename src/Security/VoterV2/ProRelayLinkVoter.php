<?php

namespace App\Security\VoterV2;

use App\Entity\MembreCentre;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProRelayLinkVoter extends Voter
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, MembreCentre::PERMISSIONS) && $subject instanceof MembreCentre;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $relay = $subject->getCentre();

        if (!$this->authorizationChecker->isGranted('UPDATE', $subject->getMembre())
            || !$this->authorizationChecker->isGranted('MANAGE_PRO', $relay)) {
            return false;
        }

        return $user->hasPermissionOnRelay($relay, $attribute);
    }
}
