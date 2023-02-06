<?php

namespace App\Repository;

use App\Entity\ClientBeneficiaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClientBeneficiaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientBeneficiaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientBeneficiaire[]    findAll()
 * @method ClientBeneficiaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientBeneficiaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientBeneficiaire::class);
    }

    public function getEntityAxel($beneficiaireId)
    {
        return $this->createQueryBuilder('cb')
            ->join('cb.entity', 'beneficiaire')
            ->andWhere('beneficiaire = :beneficiaire')
            ->setParameter('beneficiaire', $beneficiaireId)
            ->getQuery()->getOneOrNullResult();
    }
}
