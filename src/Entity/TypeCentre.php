<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * TypeCentre.
 */
class TypeCentre
{
    use GedmoTimedTrait;
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $nom;

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
     * Set nom.
     *
     * @param string $nom
     *
     * @return TypeCentre
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }
    /**
     * @var Collection
     */
    private $centres;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->centres = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Add centres.
     *
     * @return TypeCentre
     */
    public function addCentre(Centre $centres)
    {
        $this->centres[] = $centres;

        return $this;
    }

    /**
     * Remove centres.
     */
    public function removeCentre(Centre $centres)
    {
        $this->centres->removeElement($centres);
    }

    /**
     * Get centres.
     *
     * @return Collection
     */
    public function getCentres()
    {
        return $this->centres;
    }

    public function __toString()
    {
        return $this->nom;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->centres = [];
        }
    }
}
