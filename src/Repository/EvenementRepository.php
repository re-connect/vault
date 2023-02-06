<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    private function findFutureEventsByBeneficiaryQueryBuilder(Beneficiaire $beneficiary): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.rappels', 'r')
            ->andWhere('e.beneficiaire = :beneficiary')
            ->andWhere('e.date > :date')
            ->orderBy('e.date', 'ASC')
            ->setParameters([
                'beneficiary' => $beneficiary,
                'date' => new \DateTime(),
            ]);
    }

    private function searchFutureEventsByBeneficiaryQueryBuilder(Beneficiaire $beneficiary, ?string $word): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.rappels', 'r')
            ->andWhere('e.beneficiaire = :beneficiary')
            ->andWhere('e.nom LIKE :word OR e.commentaire LIKE :word OR e.date LIKE :word')
            ->andWhere('e.date > :date')
            ->orderBy('e.date', 'ASC')
            ->setParameters([
                'beneficiary' => $beneficiary,
                'word' => sprintf('%%%s%%', $word),
                'date' => new \DateTime(),
            ]);
    }

    /**
     * @return Evenement[]
     */
    public function findFutureEventsByBeneficiary(Beneficiaire $beneficiary): array
    {
        return $this->findFutureEventsByBeneficiaryQueryBuilder($beneficiary)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function findSharedFutureEventsByBeneficiary(Beneficiaire $beneficiary): array
    {
        return $this->findFutureEventsByBeneficiaryQueryBuilder($beneficiary)
            ->andWhere('e.bPrive = FALSE')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function searchFutureEventsByBeneficiary(Beneficiaire $beneficiary, string $word): array
    {
        return $this->searchFutureEventsByBeneficiaryQueryBuilder($beneficiary, $word)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function searchSharedFutureEventsByBeneficiary(Beneficiaire $beneficiary, string $word): array
    {
        return $this->searchFutureEventsByBeneficiaryQueryBuilder($beneficiary, $word)
            ->andWhere('e.bPrive = FALSE')
            ->getQuery()
            ->getResult();
    }
}
