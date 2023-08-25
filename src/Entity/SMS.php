<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;

/**
 * SMS.
 */
class SMS implements \JsonSerializable
{
    use GedmoTimedTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $dest;

    /**
     * @var Beneficiaire
     */
    private $beneficiaire;

    /**
     * @var Evenement
     */
    private $evenement;
    /**
     * @var Centre
     */
    private $centre;
    /**
     * @var Rappel
     */
    private $rappel;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDest(): string
    {
        return $this->dest;
    }

    public function setDest($dest): self
    {
        $this->dest = $dest;

        return $this;
    }

    public function getCentre(): Centre
    {
        return $this->centre;
    }

    /**
     * Set centre.
     */
    public function setCentre(Centre $centre = null): self
    {
        $this->centre = $centre;

        return $this;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return SMS
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return SMS
     */
    public function setBeneficiaire(?Beneficiaire $beneficiaire)
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    /**
     * Get evenement.
     *
     * @return Evenement
     */
    public function getEvenement()
    {
        return $this->evenement;
    }

    /**
     * Set evenement.
     *
     * @return SMS
     */
    public function setEvenement(Evenement $evenement)
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    /**
     * Get rappel.
     *
     * @return Rappel
     */
    public function getRappel()
    {
        return $this->rappel;
    }

    /**
     * Set rappel.
     *
     * @return SMS
     */
    public function setRappel(Rappel $rappel)
    {
        $this->rappel = $rappel;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->centre = null;
            $this->evenement = null;
            $this->beneficiaire = null;
            $this->rappel = null;
        }
    }

    public function __toString()
    {
        return 'SMS To String';
    }

    public static function createReminderSms(Rappel $reminder, Evenement $event, Beneficiaire $beneficiary, string $number): self
    {
        return (new SMS())
            ->setRappel($reminder)
            ->setEvenement($event)
            ->setBeneficiaire($beneficiary)
            ->setDest($number);
    }
}
