<?php

namespace App\Repository;

use App\Entity\Attributes\SharedFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SharedFolder>
 *
 * @method SharedFolder|null find($id, $lockMode = null, $lockVersion = null)
 * @method SharedFolder|null findOneBy(array $criteria, array $orderBy = null)
 * @method SharedFolder[]    findAll()
 * @method SharedFolder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SharedFolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedFolder::class);
    }
}
