<?php

namespace App\Entity;

/**
 * CreatorCentre.
 */
class CreatorCentre extends Creator
{
    /**
     * @var Centre
     */
    private $entity;

    public function __toString()
    {
        return $this->getEntity()->getNom();
    }

    /**
     * Get entity.
     *
     * @return Centre|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entity.
     *
     * @return CreatorCentre
     */
    public function setEntity(Centre $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function __construct(?Centre $relay = null)
    {
        $this->entity = $relay;
    }
}
