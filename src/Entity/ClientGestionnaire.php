<?php

namespace App\Entity;

use App\Entity\Attributes\ClientEntity;

class ClientGestionnaire extends ClientEntity
{
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
