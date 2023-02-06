<?php

namespace App\Entity;

/**
 * ConsultationBeneficiaire.
 */
class ConsultationBeneficiaire
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var Membre
     */
    private $membre;
    /**
     * @var Beneficiaire
     */
    private $beneficiaire;

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
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return ConsultationBeneficiaire
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get membre.
     *
     * @return Membre
     */
    public function getMembre()
    {
        return $this->membre;
    }

    /**
     * Set membre.
     *
     * @return ConsultationBeneficiaire
     */
    public function setMembre(Membre $membre)
    {
        $this->membre = $membre;

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

    /**
     * Set beneficiaire.
     *
     * @return ConsultationBeneficiaire
     */
    public function setBeneficiaire(Beneficiaire $beneficiaire)
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }
}
