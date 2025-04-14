<?php

namespace App\Entity\Attributes;

use App\Entity\Membre;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ClientMembre extends ClientEntity
{
    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'externalLinks')]
    protected mixed $entity = null;

    public function setEntity(?Membre $entity = null): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?Membre
    {
        return $this->entity;
    }
}
