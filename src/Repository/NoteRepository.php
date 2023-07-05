<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Note> */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * @return Note[]
     */
    public function findByBeneficiary(Beneficiaire $beneficiary, bool $isOwner, string $search = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.beneficiaire = :beneficiary')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('beneficiary', $beneficiary);

        if ($search) {
            $qb->andWhere('n.nom LIKE :search OR n.contenu LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if (!$isOwner) {
            $qb->andWhere('n.bPrive = FALSE');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
