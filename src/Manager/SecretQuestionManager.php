<?php

namespace App\Manager;

use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;

class SecretQuestionManager
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    public function currentBeneficiaryMissesSecretQuestion(): bool
    {
        $beneficiary = $this->getUser()?->getSubjectBeneficiaire();

        return $beneficiary && !$beneficiary->getQuestionSecrete();
    }
}
