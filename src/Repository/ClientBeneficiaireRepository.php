<?php

namespace App\Repository;

use App\Entity\ClientBeneficiaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ClientBeneficiaire> */
class ClientBeneficiaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientBeneficiaire::class);
    }

    public function getEntityAxel($beneficiaireId): ?ClientBeneficiaire
    {
        return $this->createQueryBuilder('cb')
            ->join('cb.entity', 'beneficiaire')
            ->andWhere('beneficiaire = :beneficiaire')
            ->setParameter('beneficiaire', $beneficiaireId)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByDistantIdAndClientName(string $distantId, string $clientName): ?ClientBeneficiaire
    {
        return $this->createQueryBuilder('cb')
            ->join('cb.client', 'c')
            ->andWhere('c.nom = :clientName')
            ->andWhere('cb.distantId = :distantId')
            ->andWhere('cb.entity_name = :entityName')
            ->setParameters([
                'clientName' => $clientName,
                'distantId' => $distantId,
                'entityName' => (new \ReflectionClass(ClientBeneficiaire::class))->getShortName(),
            ])->getQuery()->getOneOrNullResult();
    }
}
