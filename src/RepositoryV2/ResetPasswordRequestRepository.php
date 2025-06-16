<?php

namespace App\RepositoryV2;

use App\Entity\Annotations\ResetPasswordRequest;
use App\Entity\Attributes\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @method ResetPasswordRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResetPasswordRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResetPasswordRequest[]    findAll()
 * @method ResetPasswordRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        if ($user instanceof User) {
            return new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
        }

        throw new \Exception(sprintf('Error while creating reset password request at date %s : $user object is not instance of User', (new \DateTime())->format('dd/mm/YYYY')));
    }

    public function getMostRecentNonExpiredRequest(User $user): ?ResetPasswordRequest
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.requestedAt = :date')
            ->setParameters([
                'user' => $user,
                'date' => $this->getMostRecentNonExpiredRequestDate($user),
            ]);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (\Exception) {
            return null;
        }
    }
}
