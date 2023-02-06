<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Imagine\Image\Point\Center;

/**
 * CentreRepository.
 *
 * @method Centre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Centre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Centre[]    findAll()
 * @method Centre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CentreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Centre::class);
    }

    /**
     * Retourner les Bénéficiaire en attente d'acceptation.
     *
     * @return Center[]
     */
    public function findWaitingAd(Beneficiaire $beneficiaire): ?array
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->innerJoin('c.beneficiairesCentres', 'bc')
            ->innerJoin('bc.beneficiaire', 'b')
            ->where('b.id = '.$beneficiaire->getId())
            ->andWhere('bc.bValid = FALSE');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByDistantId(int|string $distantId, string $clientIdentifier): ?Centre
    {
        return $this->createQueryBuilder('centre')
            ->join('centre.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('c.distantId = :distantId')
            ->andWhere('client.randomId = :clientId')
            ->setParameters([
                'distantId' => $distantId,
                'clientId' => $clientIdentifier,
            ])
            ->getQuery()->getOneOrNullResult();
    }

    public function findByClientIdentifier(string $clientIdentifier)
    {
        return $this->createQueryBuilder('centre')
            ->join('centre.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('client.randomId = :clientId')
            ->setParameters(['clientId' => $clientIdentifier])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Centre[]
     */
    public function findPersonalRelays(Beneficiaire $beneficiary, bool $isValid): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.beneficiairesCentres', 'bc')
            ->innerJoin('bc.beneficiaire', 'b')
            ->leftJoin('c.adresse', 'a')
            ->addSelect(['bc', 'b', 'a'])
            ->where('b = :beneficiary')
            ->andWhere('bc.bValid = :isValid')
            ->setParameters([
                'beneficiary' => $beneficiary,
                'isValid' => $isValid,
            ])
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }
}
