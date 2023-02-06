<?php

namespace App\Repository;

use App\Entity\ClientEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClientEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientEntity[]    findAll()
 * @method ClientEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientEntity::class);
    }
}
