<?php

namespace App\Repository;

use App\Entity\FolderIcon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FolderIcon>
 *
 * @method FolderIcon|null find($id, $lockMode = null, $lockVersion = null)
 * @method FolderIcon|null findOneBy(array $criteria, array $orderBy = null)
 * @method FolderIcon[]    findAll()
 * @method FolderIcon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FolderIconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FolderIcon::class);
    }
}
