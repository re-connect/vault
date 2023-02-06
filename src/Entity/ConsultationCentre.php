<?php

namespace App\Entity;

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
     * @var \App\Entity\Centre
     */
    private $centre;

    /**
     * @var \App\Entity\Beneficiaire
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
     * @param \App\Entity\Centre $centre
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
     * @return \App\Entity\Centre
     */
    public function getCentre()
    {
        return $this->centre;
    }

    /**
     * Set beneficiaire.
     *
     * @param \App\Entity\Beneficiaire $beneficiaire
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
     * @return \App\Entity\Beneficiaire
     */
    public function getBeneficiaire()
    {
        return $this->beneficiaire;
    }
}
