<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CreatorCentre extends Creator implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return $this->getEntity()->getNom();
    }

    public function getEntity(): ?Centre
    {
        return $this->entity;
    }

    public function setEntity(Centre $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Centre::class)]
        private ?Centre $entity = null
    ) {
    }
}
