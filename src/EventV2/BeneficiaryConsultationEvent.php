<?php

namespace App\EventV2;

use App\Entity\Attributes\Beneficiaire;

readonly class BeneficiaryConsultationEvent
{
    public function __construct(private Beneficiaire $beneficiary)
    {
    }

    public function getBeneficiary(): Beneficiaire
    {
        return $this->beneficiary;
    }
}
