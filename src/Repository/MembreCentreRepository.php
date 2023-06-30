<?php

namespace App\Repository;

use App\Entity\MembreCentre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MembreCentre|null find($id, $lockMode = null, $lockVersion = null)
 * @method MembreCentre|null findOneBy(array $criteria, array $orderBy = null)
 * @method MembreCentre[]    findAll()
 * @method MembreCentre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembreCentreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MembreCentre::class);
    }
}
