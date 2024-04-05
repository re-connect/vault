<?php

namespace App\Security\VoterV2;

use App\Entity\Centre;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RelayVoter extends Voter
{
    public const string VIEW = 'VIEW';
    public const string MANAGE_PRO = 'MANAGE_PRO';
    public const string MANAGE_BENEFICIARIES = 'MANAGE_BENEFICIARIES';

    private const array PERMISSIONS = [self::VIEW, self::MANAGE_PRO, self::MANAGE_BENEFICIARIES];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::PERMISSIONS) && $subject instanceof Centre;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $user->getRelays()->contains($subject),
            self::MANAGE_BENEFICIARIES => $user->getAffiliatedRelaysWithBeneficiaryManagement()->contains($subject),
            self::MANAGE_PRO => $user->getAffiliatedRelaysWithProfessionalManagement()->contains($subject),
            default => false,
        };
    }
}
