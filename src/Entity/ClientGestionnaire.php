<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ClientGestionnaire extends ClientEntity
{
    #[ORM\ManyToOne(targetEntity: Centre::class, inversedBy: 'externalLinks')]
    protected mixed $entity = null;

    public function setEntity(?Gestionnaire $entity = null): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?Gestionnaire
    {
        return $this->entity;
    }
}
