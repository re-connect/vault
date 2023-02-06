<?php

namespace App\Manager;

use App\Entity\AccessToken;
use App\Entity\TokenInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class TokenManager implements TokenManagerInterface
{
    protected EntityManagerInterface $em;
    protected EntityRepository $repository;

    public function __construct(EntityManagerInterface $em)
    {
        /** @var EntityRepository $repository */
        $repository = $em->getRepository(AccessToken::class);

        $this->em = $em;
        $this->repository = $repository;
    }

    public function getClass()
    {
        return AccessToken::class;
    }

    public function findTokenBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    public function updateToken(TokenInterface $token)
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    public function deleteToken(TokenInterface $token)
    {
        $this->em->remove($token);
        $this->em->flush();
    }

    public function deleteExpired()
    {
        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->delete()
            ->where('t.expiresAt < ?1')
            ->setParameters([1 => time()]);

        return $qb->getQuery()->execute();
    }

    public function createToken()
    {
        $class = $this->getClass();

        return new $class();
    }

    public function findTokenByToken($token)
    {
        return $this->findTokenBy(['token' => $token]);
    }
}
