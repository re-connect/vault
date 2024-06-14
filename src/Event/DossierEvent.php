<?php

namespace App\Event;

use App\Entity\Beneficiaire;

class DossierEvent extends REEvent
{
    public const DOSSIER_ERROR_OUVERTURE = 1;

    protected $beneficiaire;
    protected $evenement;

    public function __construct(Beneficiaire $beneficiaire, protected $type)
    {
        $this->beneficiaire = $beneficiaire;
    }

    public function getEvenement()
    {
        return $this->evenement;
    }

    public function getType()
    {
        return $this->type;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf("Evenement (id:%s / nom:'%s' / dateRappel:%s) rappel envoyé %s", $this->evenement->getId(), $this->evenement->getNom(), $this->evenement->getDate()->format("H\hi d/m/Y"), $this->getConstName($this->type));
    }
}
