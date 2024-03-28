<?php

namespace App\Api\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Centre;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class UserCentersCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    #[\Override]
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $user = $this->security->getUser();
        if (Centre::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->leftJoin(sprintf('%s.membresCentres', $rootAlias), 'membresCentres')
            ->leftJoin(sprintf('%s.beneficiairesCentres', $rootAlias), 'beneficiairesCentres')
            ->leftJoin('beneficiairesCentres.beneficiaire', 'beneficiaire')
            ->leftJoin('membresCentres.membre', 'membre')
            ->andWhere('membre.user = :current_user OR beneficiaire.user = :current_user')
            ->setParameter('current_user', $user);
    }
}
