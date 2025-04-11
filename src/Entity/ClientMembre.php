<?php

namespace App\Entity;

use App\Entity\Attributes\ClientEntity;

class ClientMembre extends ClientEntity
{
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
