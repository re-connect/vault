<?php

namespace App\Security\VoterV2;

use App\Entity\DonneePersonnelle;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PersonalDataVoter extends Voter
{
    public const UPDATE = 'UPDATE';
    private AuthorizationCheckerInterface $checker;

    public function __construct(AuthorizationCheckerInterface $checker)
    {
        $this->checker = $checker;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return self::UPDATE === $attribute
            && ($subject instanceof DonneePersonnelle);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->isBeneficiaire()
            ? $this->checker->isGranted('UPDATE', $subject->getBeneficiaire())
            : $this->checker->isGranted('UPDATE', $subject->getBeneficiaire()) && false === $subject->getBPrive();
    }
}
