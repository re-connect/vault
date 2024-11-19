<?php

namespace App\Entity;

use App\Entity\Attributes\Centre;

class ClientCentre extends ClientEntity
{
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
