<?php

namespace App\Security\VoterV2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Security\HelperV2\UserHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BeneficiaryVoter extends Voter
{
    public const UPDATE = 'UPDATE';

    public function __construct(private readonly UserHelper $helper)
    {
    }

    /**
     * @param object $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return self::UPDATE === $attribute
            && $subject instanceof Beneficiaire;
    }

    /**
     * @param Beneficiaire $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
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
