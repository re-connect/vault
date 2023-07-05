<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Evenement> */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findFutureEventsByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, string $search = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.rappels', 'r')
            ->andWhere('e.beneficiaire = :beneficiary')
            ->andWhere('e.date > :date')
            ->orderBy('e.date', 'ASC')
            ->setParameter('beneficiary', $beneficiary)
            ->setParameter('date', new \DateTime());

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.commentaire LIKE :search OR e.date LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if (!$isOwner) {
            $qb->andWhere('e.bPrive = FALSE');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
