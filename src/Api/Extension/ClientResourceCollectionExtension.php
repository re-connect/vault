<?php

namespace App\Api\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Beneficiaire;
use App\Security\HelperV2\Oauth2Helper;
use Doctrine\ORM\QueryBuilder;

final class ClientResourceCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Oauth2Helper $oauth2Helper)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Beneficiaire::class !== $resourceClass) {
            return;
        }
        $rootAliases = $queryBuilder->getRootAliases();
        if (0 === count($rootAliases)) {
            return;
        }

        $queryBuilder
            ->innerJoin(sprintf('%s.externalLinks', $rootAliases[0]), 'externalLink')
            ->innerJoin('externalLink.client', 'client')
            ->andWhere('client = :client')
            ->setParameter('client', $this->oauth2Helper->getClient());
    }
}
