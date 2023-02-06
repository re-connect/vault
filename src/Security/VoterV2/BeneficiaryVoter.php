<?php

namespace App\Security\VoterV2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Repository\BeneficiaireRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BeneficiaryVoter extends Voter
{
    public const UPDATE = 'UPDATE';
    private BeneficiaireRepository $beneficiaryRepository;

    public function __construct(BeneficiaireRepository $beneficiaryRepository)
    {
        $this->beneficiaryRepository = $beneficiaryRepository;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return self::UPDATE === $attribute
            && $subject instanceof Beneficiaire;
    }

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

    private function canUpdateBeneficiary(User $user, $subject): bool
    {
        return match ($user->getTypeUser()) {
            User::USER_TYPE_BENEFICIAIRE => $user->getSubjectBeneficiaire() === $subject,
            default => false,
        };
    }
}
