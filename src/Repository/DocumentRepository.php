<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DocumentRepository.
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * @return mixed
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByOldCriterias($archiveName, $documentName)
    {
        $qb = $this->createQueryBuilder('d');

        $qb
            ->join(Beneficiaire::class, 'b')
            ->where('b.archiveName = :archiveName')
            ->andWhere('d.nom = :nom')
            ->setParameters(
                [
                    'archiveName' => $archiveName,
                    'nom' => $documentName,
                ]
            );

        return $qb->getQuery()->getSingleResult();
    }

    public function findAllowed(Beneficiaire $beneficiaire, bool $isBeneficiaire, ?int $dossierId = null): array
    {
        $criteria = [
            'beneficiaire' => $beneficiaire,
            'dossier' => $dossierId,
        ];
        if (!$isBeneficiaire) {
            $criteria['bPrive'] = false;
        }

        return parent::findBy($criteria, ['id' => Criteria::DESC]);
    }

    private function findByBeneficiaryQueryBuilder(Beneficiaire $beneficiary, ?Dossier $folder): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->andWhere('d.beneficiaire = :beneficiary')
            ->setParameter('beneficiary', $beneficiary)
            ->orderBy('d.createdAt', 'DESC');

        if ($folder) {
            $queryBuilder->andWhere('d.dossier = :folder')
                ->setParameter('folder', $folder);
        } else {
            $queryBuilder->andWhere('d.dossier IS NULL');
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
            $qb->andWhere('d.dossier = :folder');
            $parameters['folder'] = $folder->getId();
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->setParameters($parameters);
    }

    /**
     * @return Document[]
     */
    public function findAllByBeneficiary(Beneficiaire $beneficiary, ?Dossier $folder = null): array
    {
        return $this->findByBeneficiaryQueryBuilder($beneficiary, $folder)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Document[]
     */
    public function findSharedByBeneficiary(Beneficiaire $beneficiary, ?Dossier $folder = null): array
    {
        return $this->findByBeneficiaryQueryBuilder($beneficiary, $folder)
            ->andWhere('d.bPrive = FALSE')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Document[]
     */
    public function searchByBeneficiary(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $this->searchByBeneficiaryQueryBuilder($beneficiary, $word, $folder)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Document[]
     */
    public function searchSharedByBeneficiary(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $this->searchByBeneficiaryQueryBuilder($beneficiary, $word, $folder)
            ->andWhere('d.bPrive = FALSE')
            ->getQuery()
            ->getResult();
    }
}
