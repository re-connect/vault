<?php

namespace App\Event;

use App\Entity\Rappel;

class EvenementEvent extends REEvent
{
    public const EVENEMENT_RAPPEL_SMS = 1;
    public const EVENEMENT_RAPPEL_MAIL = 2;

    protected $evenement;
    protected $type;
    protected $rappel;

    public function __construct(Rappel $rappel, $type)
    {
        $this->rappel = $rappel;
        $this->evenement = $rappel->getEvenement();
        $this->type = $type;
    }

    public function getEvenement()
    {
        return $this->evenement;
    }

    public function getRappel()
    {
        return $this->rappel;
    }

    public function getType()
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return sprintf("Evenement (id:%s / nom:'%s' / dateRappel:%s) rappel envoyé %s", $this->evenement->getId(), $this->evenement->getNom(), $this->evenement->getDate()->format("H\hi d/m/Y"), $this->getConstName($this->type));
    }
}
