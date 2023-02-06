<?php

namespace App\Entity;

/**
 * CreatorClient.
 */
class CreatorClient extends Creator
{
    /**
     * @var Client
     */
    private $entity;

    public function __toString()
    {
        return $this->getEntity()->getNom();
    }

    /**
     * Get entity.
     *
     * @return Client|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entity.
     *
     * @return CreatorClient
     */
    public function setEntity(Client $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function __construct(?Client $client = null)
    {
        $this->entity = $client;
    }
}
