<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
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

    public function getEntity(): ?Client
    {
        return $this->entity;
    }

    public function setEntity(Client $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Client::class)]
        private ?Client $entity = null
    ) {
    }
}
