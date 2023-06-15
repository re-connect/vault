<?php

namespace App\Security\VoterV2;

use App\Entity\MembreCentre;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProVoter extends Voter
{
    public const MANAGE = 'MANAGE_PRO';

    protected function supports(string $attribute, $subject): bool
    {
        return self::MANAGE == $attribute;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $user->hasDroit(MembreCentre::TYPEDROIT_GESTION_MEMBRES);
    }
}
