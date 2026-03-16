<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ClientCentre extends ClientEntity
{
    #[ORM\ManyToOne(targetEntity: Centre::class, inversedBy: 'externalLink')]
    protected mixed $entity = null;

    public function setEntity(?Centre $entity = null): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?Centre
    {
        return $this->entity;
    }
}
