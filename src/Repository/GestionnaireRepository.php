<?php

namespace App\Repository;

use App\Entity\Gestionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Gestionnaire> */
class GestionnaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gestionnaire::class);
    }

    public function countKPI()
    {
        $qb = $this->createQueryBuilder('g');

        $qb->select('count(g.id)')
            ->join('g.user', 'user')
            ->where('user.test = false');

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
