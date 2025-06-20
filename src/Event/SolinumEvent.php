<?php

namespace App\Event;

use App\Entity\Beneficiaire;

class SolinumEvent extends REEvent
{
    public const SOLINUM_SMS = 1;
    public const SOLINUM_WEB = 2;
    protected $user;

    public function __construct(protected $type, protected ?Beneficiaire $beneficiaire = null)
    {
        $this->context = [
            'beneficiaire_id' => (null === $this->beneficiaire) ? false : $this->beneficiaire->getId(),
        ];
    }

    public function getBeneficaire(): ?Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function getType()
    {
        return $this->type;
    }
}
