<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Evenement> */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function findEventsByBeneficiaryQueryBuilder(Beneficiaire $beneficiary, bool $isOwner, string $search = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.rappels', 'r')
            ->andWhere('e.beneficiaire = :beneficiary')
            ->orderBy('e.date', 'ASC')
            ->setParameter('beneficiary', $beneficiary);

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.commentaire LIKE :search OR e.date LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if (!$isOwner) {
            $qb->andWhere('e.bPrive = FALSE');
        }

        return $qb;
    }

    /**
     * @return Evenement[]
     */
    public function findFutureEventsByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, string $search = null): array
    {
        $now = new \DateTime();
        $nowLess12h05 = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'-12 hours -5 minutes')));

        return $this->findEventsByBeneficiaryQueryBuilder($beneficiary, $isOwner, $search)
            ->andWhere('e.date > :nowLess12h05')
            ->setParameter('nowLess12h05', $nowLess12h05)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function findPastEventsByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, string $search = null): array
    {
        $now = new \DateTime();
        $nowPlus12h05 = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'+12 hours -5 minutes')));

        return $this->findEventsByBeneficiaryQueryBuilder($beneficiary, $isOwner, $search)
            ->andWhere('e.date < :nowPlus12h05')
            ->setParameter('nowPlus12h05', $nowPlus12h05)
            ->getQuery()
            ->getResult();
    }
}
