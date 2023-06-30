<?php

namespace App\Repository;

use App\Entity\Rappel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Rappel> */
class RappelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rappel::class);
    }

    /**
     * @return Rappel[]
     *
     * @throws \Exception
     */
    public function getDueReminders(): array
    {
        $now = new \DateTime();
        $nowLess12h05 = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'-12 hours -5 minutes')));
        $nowPlus12h = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'+14 hours')));

        return $this->createQueryBuilder('r')
            ->innerJoin('r.evenement', 'e')
            ->innerJoin('e.beneficiaire', 'b')
            ->innerJoin('b.user', 'u')
            ->addSelect(['e', 'b', 'u'])
            ->where('e.date > :nowLess12h05')
            ->andWhere('r.bEnvoye = false')
            ->andWhere('r.date > :nowLess12h05')
            ->andWhere('r.date < :nowPlus12h')
            ->setParameters([
                'nowPlus12h' => $nowPlus12h,
                'nowLess12h05' => $nowLess12h05,
            ])
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }
}
