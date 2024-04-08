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
    public function findFutureEventsByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, ?string $search = null): array
    {
        $now = new \DateTime();
        $nowLess12h05 = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'-12 hours -5 minutes')));

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.rappels', 'r')
            ->andWhere('e.beneficiaire = :beneficiary')
            ->andWhere('e.date > :nowLess12h05')
            ->orderBy('e.date', 'ASC')
            ->setParameter('beneficiary', $beneficiary)
            ->setParameter('nowLess12h05', $nowLess12h05);

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
