<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CreatorUser extends Creator implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return $this->getEntity()->toSonataString();
    }

    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        private ?User $entity = null
    ) {
    }

    /**
     * Get entity.
     *
     * @return User|null
     */
    public function getEntity(): ?User
    {
        return $this->entity;
    }

    /**
     * Set entity.
     *
     * @return CreatorUser
     */
    public function setEntity(User $entity): static
    {
        $this->entity = $entity;

        return $this;
    }
}
