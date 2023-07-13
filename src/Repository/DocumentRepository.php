<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Document> */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
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

    public function findAllowed(Beneficiaire $beneficiaire, bool $isBeneficiaire, int $dossierId = null): array
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

    /**
     * @return Document[]
     */
    public function findByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, Dossier $folder = null, string $search = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.beneficiaire = :beneficiary')
            ->orderBy('d.createdAt', 'DESC')
            ->setParameter('beneficiary', $beneficiary);

        if ($search) {
            $qb->andWhere('d.nom LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if ($folder) {
            $qb->andWhere('d.dossier = :folder')
                ->setParameter('folder', $folder);
        } else {
            // We want to fetch only root documents when we are not searching and we are not inside a folder
            if (!$search) {
                $qb->andWhere('d.dossier IS NULL');
            }
        }

        if (!$isOwner) {
            $qb->andWhere('d.bPrive = FALSE');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
