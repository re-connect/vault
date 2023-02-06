<?php

namespace App\Entity;

/**
 * CreatorUser.
 */
class CreatorUser extends Creator
{
    /**
     * @var User
     */
    private $entity;

    public function __toString()
    {
        return $this->getEntity()->toSonataString();
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
