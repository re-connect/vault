<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Contact> */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return Contact[]
     */
    public function findByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.beneficiaire = :beneficiary')
            ->orderBy('c.createdAt', 'DESC')
            ->setParameter('beneficiary', $beneficiary);

        if ($search) {
            $qb->andWhere('c.nom LIKE :search OR c.prenom LIKE :search OR c.telephone LIKE :search or c.email LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if (!$isOwner) {
            $qb->andWhere('c.bPrive = FALSE');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
