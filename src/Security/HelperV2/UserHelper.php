<?php

namespace App\Security\HelperV2;

use App\Entity\Beneficiaire;
use App\Entity\User;

class UserHelper
{
    public function canManageBeneficiary(User $user, Beneficiaire $beneficiary): bool
    {
        return 0 < count(array_intersect(
            $user->getAffiliatedRelaysWithBeneficiaryManagement()->toArray(),
            $beneficiary->getAffiliatedRelays()->toArray(),
        ));
    }
}
