<?php

namespace App\Entity\Interface;

use App\Entity\Client;
use App\Entity\ClientEntity;
use Doctrine\Common\Collections\Collection;

interface ClientResourceInterface
{
    /** @return Collection<int, ClientEntity> */
    public function getExternalLinks(): Collection;

    public function hasExternalLinkForClient(Client $client): bool;

    public function getExternalLinkForClient(Client $client): ?ClientEntity;
}
