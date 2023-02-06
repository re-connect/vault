<?php

namespace App\Entity;

/**
 * PrixCentre.
 */
class PrixCentre
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $budget;

    /**
     * @var float
     */
    private $prix;

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
     * Set budget.
     *
     * @param string $budget
     *
     * @return PrixCentre
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get budget.
     *
     * @return string
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * Set prix.
     *
     * @param float $prix
     *
     * @return PrixCentre
     */
    public function setPrix($prix)
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * Get prix.
     *
     * @return float
     */
    public function getPrix()
    {
        return $this->prix;
    }
}
