<?php

namespace App\Security\HelperV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Membre;
use App\Entity\Attributes\User;

class UserHelper
{
    public function canUpdateBeneficiary(User $user, Beneficiaire $beneficiary): bool
    {
        return 0 < count(array_intersect(
            $user->getAffiliatedRelaysWithBeneficiaryManagement()->toArray(),
            $beneficiary->getAffiliatedRelays()->toArray(),
        ));
    }

    public function canUpdateProfessional(User $user, Membre $professional): bool
    {
        return 0 < count(array_intersect(
            $user->getAffiliatedRelaysWithProfessionalManagement()->toArray(),
            $professional->getCentres()->toArray(),
        ));
    }
}
