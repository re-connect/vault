<?php

namespace App\Security\HelperV2;

use App\Entity\Beneficiaire;
use App\Entity\User;

class UserHelper
{
    public function canManageBeneficiary(User $user, Beneficiaire $beneficiary): bool
    {
        $validBeneficiaryRelays = $beneficiary->getAffiliatedRelays()->toArray();
        $validProRelays = [];

        if ($user->isMembre()) {
            $validProRelays = $user->getSubjectMembre()->getAffiliatedRelaysWithBeneficiaryManagement()->toArray();
        } elseif ($user->isGestionnaire()) {
            $validProRelays = $user->getSubjectGestionnaire()->getCentres()->toArray();
        }

        return 0 < count(array_intersect($validProRelays, $validBeneficiaryRelays));
    }
}
