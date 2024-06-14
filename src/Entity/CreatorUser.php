<?php

namespace App\Entity;

/**
 * CreatorUser.
 */
class CreatorUser extends Creator implements \Stringable
{
    public function __toString(): string
    {
        return $this->getEntity()->toSonataString();
    }

    public function __construct(
        private ?User $entity = null
    ) {
    }

    /**
     * Get entity.
     *
     * @return User|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entity.
     *
     * @return CreatorUser
     */
    public function setEntity(User $entity)
    {
        $this->entity = $entity;

        return $this;
    }
}
