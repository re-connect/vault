<?php

namespace App\Entity;

use App\Entity\Attributes\Centre;
use App\Traits\GedmoTimedTrait;

/**
 * ConsultationCentre.
 */
class ConsultationCentre
{
    use GedmoTimedTrait;
    /**
     * @var int
     */
    private $id;

    /**
     * @var Centre
     */
    private $centre;

    /**
     * @var Beneficiaire
     */
    private $beneficiaire;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set centre.
     *
     * @return ConsultationCentre
     */
    public function setCentre(Centre $centre)
    {
        $this->centre = $centre;

        return $this;
    }

    /**
     * Get centre.
     *
     * @return Centre
     */
    public function getCentre()
    {
        return $this->centre;
    }

    /**
     * Set beneficiaire.
     *
     * @return ConsultationCentre
     */
    public function setBeneficiaire(Beneficiaire $beneficiaire)
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    /**
     * Get beneficiaire.
     *
     * @return Beneficiaire
     */
    public function getBeneficiaire()
    {
        return $this->beneficiaire;
    }
}
