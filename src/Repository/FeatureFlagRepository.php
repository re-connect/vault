<?php

namespace App\Repository;

use App\Entity\FeatureFlag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeatureFlag>
 *
 * @method FeatureFlag|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeatureFlag|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeatureFlag[]    findAll()
 * @method FeatureFlag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeatureFlagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }
}
