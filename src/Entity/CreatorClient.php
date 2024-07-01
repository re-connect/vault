<?php

namespace App\Entity;

/**
 * CreatorClient.
 */
class CreatorClient extends Creator implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return (string) $this->getEntity()->getNom();
    }

    public function getNom(): ?string
    {
        return $this->getEntity()?->getNom();
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

    public function __construct(
        private ?Client $entity = null
    ) {
    }
}
