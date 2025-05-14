<?php

namespace App\Entity;

use App\Entity\Attributes\Centre;
use App\Entity\Attributes\Creator;

/**
 * CreatorCentre.
 */
class CreatorCentre extends Creator implements \Stringable
{
    #[\Override]
    public function __toString(): string
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

    public function __construct(
        private ?Centre $entity = null
    ) {
    }
}
