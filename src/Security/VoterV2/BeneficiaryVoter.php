<?php

namespace App\Security\VoterV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Security\HelperV2\UserHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BeneficiaryVoter extends Voter
{
    public const string MANAGE = 'MANAGE_BENEFICIARIES';
    public const string UPDATE = 'UPDATE';

    public function __construct(private readonly UserHelper $helper)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        if (self::MANAGE === $attribute && !$subject) {
            return true;
        }

        if (self::UPDATE === $attribute && $subject instanceof Beneficiaire) {
            return true;
        }

        return false;
    }

    /**
     * @param Beneficiaire $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE => $user->hasDroit(MembreCentre::MANAGE_BENEFICIARIES_PERMISSION),
            self::UPDATE => $this->canUpdateBeneficiary($user, $subject),
            default => false,
        };
    }

    private function canUpdateBeneficiary(User $user, Beneficiaire $subject): bool
    {
        return match ($user->getTypeUser()) {
            User::USER_TYPE_BENEFICIAIRE => $user->getSubjectBeneficiaire() === $subject,
            User::USER_TYPE_MEMBRE => $this->helper->canUpdateBeneficiary($user, $subject),
            default => false,
        };
    }
}
