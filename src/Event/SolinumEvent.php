<?php

namespace App\Event;

use App\Entity\Beneficiaire;

class SolinumEvent extends REEvent
{
    public const SOLINUM_SMS = 1;
    public const SOLINUM_WEB = 2;

    protected $beneficiaire;
    protected $type;
    protected $user;

    public function __construct($type, Beneficiaire $beneficiaire = null)
    {
        $this->beneficiaire = $beneficiaire;
        $this->type = $type;

        $this->context = [
            'beneficiaire_id' => (null === $beneficiaire) ? false : $beneficiaire->getId(),
        ];
    }

    public function getBeneficaire()
    {
        return $this->beneficiaire;
    }

    public function getType()
    {
        return $this->type;
    }
}
