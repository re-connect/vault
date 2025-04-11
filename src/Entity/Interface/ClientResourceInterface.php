<?php

namespace App\Entity\Interface;

use App\Entity\Attributes\Client;
use App\Entity\Attributes\ClientEntity;
use Doctrine\Common\Collections\Collection;

interface ClientResourceInterface
{
    /** @return Collection<int, ClientEntity> */
    public function getExternalLinks(): Collection;

    public function hasExternalLinkForClient(Client $client): bool;

    public function getExternalLinkForClient(Client $client): ?ClientEntity;
}
