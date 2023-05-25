<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DossierRepository.
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * @return Dossier[]
     */
    public function findAllowed(Beneficiaire $beneficiaire, bool $isBeneficiaire, ?int $dossierParentId = null): array
    {
        $criteria = [
            'beneficiaire' => $beneficiaire,
            'dossierParent' => $dossierParentId,
        ];
        if (!$isBeneficiaire) {
            $criteria['bPrive'] = false;
        }

        return parent::findBy($criteria, ['nom' => Criteria::ASC]);
    }

    private function findByBeneficiaryQueryBuilder(Beneficiaire $beneficiary, ?Dossier $parentFolder): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->andWhere('d.beneficiaire = :beneficiary')
            ->setParameter('beneficiary', $beneficiary)
            ->orderBy('d.nom', 'ASC');

        if ($parentFolder) {
            $queryBuilder->andWhere('d.dossierParent = :parentFolder')
                ->setParameter('parentFolder', $parentFolder);
        } else {
            $queryBuilder->andWhere('d.dossierParent IS NULL');
        }

        return $queryBuilder;
    }

    private function searchByBeneficiaryQueryBuilder(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.beneficiaire = :beneficiary')
            ->andWhere('d.nom LIKE :word');

        $parameters = [
            'beneficiary' => $beneficiary,
            'word' => sprintf('%%%s%%', $word),
        ];

        if ($folder) {
            $qb->andWhere('d.dossierParent = :folder');
            $parameters['folder'] = $folder->getId();
        }

        return $qb->orderBy('d.nom', 'ASC')
            ->setParameters($parameters);
    }

    /**
     * @return Dossier[]
     */
    public function findAllByBeneficiary(Beneficiaire $beneficiary, ?Dossier $parentFolder = null): array
    {
        return $this->findByBeneficiaryQueryBuilder($beneficiary, $parentFolder)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findSharedByBeneficiary(Beneficiaire $beneficiary, ?Dossier $parentFolder = null): array
    {
        return $this->findByBeneficiaryQueryBuilder($beneficiary, $parentFolder)
            ->andWhere('d.bPrive = FALSE')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function searchByBeneficiary(Beneficiaire $beneficiary, string $word, Dossier $folder = null): array
    {
        return $this->searchByBeneficiaryQueryBuilder($beneficiary, $word, $folder)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function searchSharedByBeneficiary(Beneficiaire $beneficiary, string $word, Dossier $folder = null): array
    {
        return $this->searchByBeneficiaryQueryBuilder($beneficiary, $word, $folder)
            ->getQuery()
            ->getResult();
    }
}
