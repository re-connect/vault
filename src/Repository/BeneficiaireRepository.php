<?php

namespace App\Repository;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Beneficiaire> */
class BeneficiaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Beneficiaire::class);
    }

    /**
     * Retourne le Beneficiaire qui sont liés à un centre et en cours de création
     * c'est à dire que la création n'est pas allé jusqu'au bout.
     */
    public function findByIsCreatingJoinCentre(): array
    {
        $qb = $this->createQueryBuilder('b');
        $isCreating = true;

        $qb
            ->join(BeneficiaireCentre::class, 'bc')
            ->where('b.isCreating = :isCreating')
            ->setParameter('isCreating', $isCreating);

        return $qb->getQuery()->getResult();
    }

    public function findRosalies(): array
    {
        $qb = $this->createQueryBuilder('b');

        $qb
            ->where('b.idRosalie IS NOT NULL');

        return $qb->getQuery()->getResult();
    }

    public function findByDistantId(int|string $distantId, string $clientIdentifier): ?Beneficiaire
    {
        try {
            return $this->createQueryBuilder('b')
                ->join('b.externalLinks', 'c')
                ->join('c.client', 'client')
                ->andWhere('c.distantId = :distantId AND client.randomId = :clientId')
                ->setParameters([
                    'distantId' => $distantId,
                    'clientId' => $clientIdentifier,
                ])->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function findByDistantIds(array $distantIds, string $clientIdentifier): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('c.distantId IN (:distantIds)')
            ->andWhere('client.randomId = :clientId')
            ->setParameters([
                'distantIds' => $distantIds,
                'clientId' => $clientIdentifier,
            ])->getQuery()->getResult();
    }

    public function findByUsername(string $username): ?Beneficiaire
    {
        return $this->createQueryBuilder('b')
            ->join('b.user', 'user')
            ->where('user.username = :username')
            ->setParameter('username', $username)
            ->getQuery()->getOneOrNullResult();
    }

    public function countKPI()
    {
        $qb = $this
            ->createQueryBuilder('b')
            ->select('count(b.id)')
            ->join('b.user', 'user')
            ->where('isCreating = false')
            ->andWhere('user.test = false');

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception) {
            return 0;
        }
    }

    public function getEntitiesAxel()
    {
        return $this->createQueryBuilder('b')
            ->join('b.externalLinks', 'c')
            ->andWhere('c.client = :clientId')
            ->setParameter('clientId', 6)
            ->getQuery()->getResult();
    }

    public function findByClientIdentifier(string $clientIdentifier): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('client.randomId = :clientId')
            ->setParameters(['clientId' => $clientIdentifier])
            ->getQuery()
            ->getResult();
    }

    public function getBeneficiariesSiSiaoNumbers(?Client $client): array
    {
        //        return $this->createQueryBuilder('b')
        //            ->select('b.siSiaoNumber')
        //            ->andWhere('b.siSiaoNumber IS NOT NULL')
        //            ->getQuery()
        //            ->getArrayResult();
        return $this->createQueryBuilder('b')
            ->select('b.id, c.distantId')
            ->join('b.externalLinks', 'c')
            ->join('c.client', 'client')
            ->andWhere('client.randomId = :clientId')
            ->andWhere('c.distantId IS NOT NULL')
            ->andWhere("c.distantId LIKE 'SI-%' ")
            ->setParameters(['clientId' => $client->getRandomId()])
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return Beneficiaire[]
     */
    public function searchByUsernameInformation(?string $firstname, ?string $lastname, ?\DateTime $birthDate): array
    {
        $parameters = [];
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.user', 'u')
            ->where('b.isCreating = false');

        if ($firstname) {
            $qb->andWhere('u.prenom LIKE :firstname');
            $parameters['firstname'] = sprintf('%%%s%%', $firstname);
        }

        if ($lastname) {
            $qb->andWhere('u.nom LIKE :lastname');
            $parameters['lastname'] = sprintf('%%%s%%', $lastname);
        }

        if ($birthDate) {
            $qb->andWhere('b.dateNaissance = :birthDate');
            $parameters['birthDate'] = $birthDate;
        }
        $qb->setParameters($parameters)
            ->orderBy('u.nom');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Beneficiaire[]
     */
    public function findByAuthorizedProfessional(Membre $professional, string $search = null, Centre $relay = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.beneficiairesCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->innerJoin('b.user', 'u')
            ->innerJoin('c.membresCentres', 'mc')
            ->innerJoin('mc.membre', 'm')
            ->addSelect('u')
            ->addSelect('bc')
            ->addSelect('c')
            ->andWhere('b.isCreating = false')
            ->andWhere('bc.bValid = true')
            ->andWhere('m.id = :id')
            ->andWhere('mc.bValid = true')
            ->andWhere('mc.droits LIKE :access')
            ->setParameter('id', $professional->getId())
            ->setParameter('access', sprintf('%%"%s";b:1%%', MembreCentre::MANAGE_BENEFICIARIES_PERMISSION));

        if ($relay) {
            $qb->andWhere('c = :relay')
                ->setParameter('relay', $relay);
        }

        if ($search) {
            $qb->andWhere('u.username LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        return $qb->orderBy('u.username')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }
}
