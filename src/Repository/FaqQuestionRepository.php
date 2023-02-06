<?php

namespace App\Repository;

use App\Entity\FaqQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FaqQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method FaqQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method FaqQuestion[]    findAll()
 * @method FaqQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FaqQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqQuestion::class);
    }

    public function findGreaterThanOrEqual(?int $position): array
    {
        return $this->createQueryBuilder('f')
            ->select('f')
            ->where('f.position >= :position')
            ->orderBy('f.position', 'ASC')
            ->setParameter('position', $position)
            ->getQuery()
            ->getResult();
    }
}
