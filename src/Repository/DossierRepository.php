<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Dossier> */
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

    /**
     * @return Dossier[]
     */
    public function findByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, Dossier $parentFolder = null, string $search = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.beneficiaire = :beneficiary')
            ->orderBy('d.nom', 'ASC')
            ->setParameter('beneficiary', $beneficiary);

        if ($search) {
            $qb->andWhere('d.nom LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if ($parentFolder) {
            $qb->andWhere('d.dossierParent = :parentFolder')
                ->setParameter('parentFolder', $parentFolder);
        } else {
            // We want to fetch only root folders when we are not searching and we are not inside a folder
            if (!$search) {
                $qb->andWhere('d.dossierParent IS NULL');
            }
        }

        if (!$isOwner) {
            $qb->andWhere('d.bPrive = FALSE');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
