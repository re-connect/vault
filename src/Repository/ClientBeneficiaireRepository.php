<?php

namespace App\Repository;

use App\Entity\ClientBeneficiaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ClientBeneficiaire> */
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
