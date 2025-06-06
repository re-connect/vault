<?php

namespace App\Security\VoterV2;

use App\Entity\Attributes\Membre;
use App\Entity\Attributes\MembreCentre;
use App\Entity\Attributes\User;
use App\Security\HelperV2\UserHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProVoter extends Voter
{
    public const MANAGE = 'MANAGE_PRO';
    public const UPDATE = 'UPDATE';

    public function __construct(private readonly UserHelper $helper)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        if (self::MANAGE === $attribute && !$subject) {
            return true;
        }

        if (self::UPDATE === $attribute && $subject instanceof Membre) {
            return true;
        }

        return false;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE => $user->hasDroit(MembreCentre::MANAGE_PROS_PERMISSION),
            self::UPDATE => $this->helper->canUpdateProfessional($user, $subject),
            default => false,
        };
    }
}
