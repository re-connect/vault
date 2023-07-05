<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array
     *
     * @throws \Exception
     *                    Récupérer les Utilisateurs n'ayant pas réinitialisé leur mot de passe dans les 24h après l'envoi du sms de réinitialisation
     *                    Concrètement, on récupère les utilisateurs ayant le champs 'smsPasswordResetDate' non null et 'smsPasswordResetDate' antérieur 24h
     */
    public function FindBySmsPasswordResetCodeFromYesterday()
    {
        // date de maintenant
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        // on récupère la date d'hier
        $yesterday = date('Y-m-d  H:i:s', strtotime('-1 day', strtotime($now)));

        // récupération du query builder de l'entité courante
        $qb = $this->createQueryBuilder('a');

        // on veut récupérer les users qui ont leur date de réinitialsation antérieur à hier
        $qb
            ->where('a.smsPasswordResetDate < :yesterday ')
            ->setParameter('yesterday', $yesterday);

        // et qui ont leur champ de code non null
        $this->andWhereColumnsNotNull($qb, ['smsPasswordResetCode']);

        // par ordre chronologique ascendant (optionel)
        $qb
            ->orderBy('a.smsPasswordResetDate', 'ASC');

        // retourne un tableau de d'objet User ou tableau vide
        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $columns
     *                              Ajout au 'query builder' les colonnes spécifié et non null
     */
    public function andWhereColumnsNotNull($qb, $columns)
    {
        foreach ($columns as $column) {
            $qb
                ->andWhere('a.'.$column.' IS NOT NULL');
        }
    }

    /**
     * @param array $columns
     *
     * @return array
     *               Recherche les entitées ayant les champs spécifiés non null
     */
    public function whereColumnsNotNull($columns)
    {
        $qb = $this->createQueryBuilder('a');

        foreach ($columns as $key => $column) {
            // si il s'agit du premier élément
            reset($columns);
            if ($key === key($columns)) {
                $qb
                    ->where('a.'.$column.' IS NOT NULL');
            }

            end($columns);
            // si il ne s'agit pas du premier élément
            if ($key === key($columns)) {
                $qb
                    ->andWhere('a.'.$column.' IS NOT NULL');
            }
        }

        // retourne un tableau de d'objet User ou tableau vide
        return $qb->getQuery()->getResult();
    }

    /**
     * @return User[]
     */
    public function findBeneficiairesByCriterias($criterias)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->join('u.subjectBeneficiaire', 'b')
            ->where('b.isCreating = FALSE');

        $i = 0;
        foreach ($criterias as $key => $criteria) {
            $param = 'param'.$i++;
            $qb->andWhere($key.' LIKE :'.$param);
            if ($criteria instanceof \DateTime) {
                $date = $criteria->format('Y-m-d');
                $qb->setParameter($param, $date);
            } else {
                $qb->setParameter($param, '%'.$criteria.'%');
            }
        }

        $qb
            ->orderBy('u.nom');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return User[]
     */
    public function findMembresByCriterias($criterias)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.typeUser = :typeUser')
            ->setParameter('typeUser', User::USER_TYPE_MEMBRE);

        $i = 0;
        foreach ($criterias as $key => $criteria) {
            $param = 'param'.$i++;
            $qb->andWhere($key.' LIKE :'.$param);
            $qb->setParameter($param, '%'.$criteria.'%');
        }

        $qb
            ->orderBy('u.nom');

        return $qb->getQuery()->getResult();
    }

    public function findByUsername($username): ?User
    {
        $query = $this->createQueryBuilder('user')
            ->where('user.username = :username')
            ->setParameter('username', $username)
            ->getQuery();

        try {
            $user = $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\User u
                WHERE u.username = :query
                OR u.email = :query
                OR u.oldUsername = :query'
        )
            ->setParameter('query', $identifier);

        try {
            $user = $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }

        return $user;
    }

    public function loadUserByUsername(string $usernameOrEmail): ?User
    {
        return $this->loadUserByIdentifier($usernameOrEmail);
    }

    /** @return User[] */
    public function findHomonyms(string $baseUsername): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :baseUsername OR u.username LIKE :formattedBaseUsername')
            ->setParameters([
                'baseUsername' => $baseUsername,
                'formattedBaseUsername' => sprintf('%%%s-%%', $baseUsername),
            ])
            ->getQuery()
            ->getResult();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}
