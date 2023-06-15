<?php

namespace App\Repository;

use App\Entity\Centre;
use App\Entity\Membre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Membre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Membre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Membre[]    findAll()
 * @method Membre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membre::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByDistantId(null|int|string $distantId, string $clientIdentifier): ?Membre
    {
        if (!$distantId) {
            return null;
        }

        return $this->createQueryBuilder('m')
            ->join('m.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('c.distantId = :distantId')
            ->andWhere('client.randomId = :clientId')
            ->setParameters([
                'distantId' => $distantId,
                'clientId' => $clientIdentifier,
            ])->getQuery()->getOneOrNullResult();
    }

    public function countKPI()
    {
        try {
            return $this->createQueryBuilder('m')
                ->select('count(m.id)')
                ->join('m.user', 'user')
                ->where('user.test = false')
                ->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function findByClientIdentifier(string $clientIdentifier)
    {
        return $this->createQueryBuilder('m')
            ->join('m.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('client.randomId = :clientId')
            ->setParameters(['clientId' => $clientIdentifier])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Membre[]
     */
    public function findByAuthorizedProfessional(Membre $professional, string $search = null, Centre $relay = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->innerJoin('m.membresCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->innerJoin('m.user', 'u')
            ->andWhere('bc.bValid = true')
            ->andWhere('m != :professional')
            ->orderBy('u.username')
            ->setParameter('professional', $professional);

        if ($relay) {
            $qb->andWhere('c = :relay')
                ->setParameter('relay', $relay);
        } else {
            $qb->andWhere('c IN (:relays)')
                ->setParameter('relays', $professional->getAffiliatedRelaysWithProfessionalManagement()->toArray());
        }

        if ($search) {
            $qb->andWhere('u.username LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        return $qb->getQuery()
            ->getResult();
    }
}
