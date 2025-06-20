<?php

namespace App\Manager;

use App\Entity\Attributes\AccessToken;
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

    #[\Override]
    public function getClass(): string
    {
        return AccessToken::class;
    }

    #[\Override]
    public function findTokenBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    #[\Override]
    public function updateToken(TokenInterface $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    #[\Override]
    public function deleteToken(TokenInterface $token): void
    {
        $this->em->remove($token);
        $this->em->flush();
    }

    #[\Override]
    public function deleteExpired()
    {
        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->delete()
            ->where('t.expiresAt < ?1')
            ->setParameters([1 => time()]);

        return $qb->getQuery()->execute();
    }

    #[\Override]
    public function createToken()
    {
        $class = $this->getClass();

        return new $class();
    }

    #[\Override]
    public function findTokenByToken($token)
    {
        return $this->findTokenBy(['token' => $token]);
    }
}
