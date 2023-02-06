<?php

namespace App\Repository;

use App\Entity\BeneficiaireCentre;
use Doctrine\ORM\EntityRepository;

/**
 * BeneficiaireCentreRepository.
 *
 * @method BeneficiaireCentre|null find($id, $lockMode = null, $lockVersion = null)
 * @method BeneficiaireCentre|null findOneBy(array $criteria, array $orderBy = null)
 * @method BeneficiaireCentre[]    findAll()
 * @method BeneficiaireCentre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeneficiaireCentreRepository extends EntityRepository
{
    public function getEntityAxel($beneficiaireId)
    {
        return $this->createQueryBuilder('bc')
            ->join('bc.beneficiaire', 'beneficiaire')
            ->join('bc.centre', 'c')
            ->join('c.externalLinks', 'cel')
            ->andWhere('cel.client = :clientId')
            ->andWhere('beneficiaire = :beneficiaire')
            ->setParameters([
                'clientId' => 6,
                'beneficiaire' => $beneficiaireId,
            ])->getQuery()->getOneOrNullResult();
    }
}
