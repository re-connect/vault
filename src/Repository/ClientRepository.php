<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/** @extends ServiceEntityRepository<Client> */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findForNewClient(?ClientEntityInterface $client): ?Client
    {
        if (!$client) {
            return null;
        }

        return $this->findForNewClientIdentifier($client->getIdentifier()) ?? $this->findByClientIdentifier($client->getIdentifier());
    }

    public function findForNewClientIdentifier(?string $clientId): ?Client
    {
        return !$clientId ? null : $this->findOneBy(['newClientIdentifier' => $clientId]);
    }

    public function findByClientIdentifier(?string $clientId): ?Client
    {
        return !$clientId ? null : $this->findOneBy(['randomId' => $clientId]);
    }
}
