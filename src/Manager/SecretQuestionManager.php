<?php

namespace App\Manager;

use App\Entity\Beneficiaire;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;

class SecretQuestionManager
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    public function beneficiaryMissesSecretQuestion(?Beneficiaire $beneficiary): bool
    {
        return $beneficiary && !$beneficiary->getQuestionSecrete();
    }

    public function getCurrentBeneficiary(): ?Beneficiaire
    {
        return $this->getUser()?->getSubjectBeneficiaire();
    }
}
