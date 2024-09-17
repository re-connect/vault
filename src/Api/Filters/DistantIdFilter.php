<?php

namespace App\Api\Filters;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Api\Manager\ApiClientManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DistantIdFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly ApiClientManager $apiClientManager,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    #[\Override]
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // otherwise filter is applied to order and page as well
        if ('distantId' !== $property) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (!in_array('externalLink', $queryBuilder->getAllAliases())) {
            $queryBuilder
                ->innerJoin(sprintf('%s.externalLinks', $rootAlias), 'externalLink')
                ->innerJoin('externalLink.client', 'client')
                ->andWhere('client = :client')
                ->setParameter('client', $this->apiClientManager->getCurrentOldClient());
        }

        $queryBuilder
            ->andWhere('externalLink.distantId = :distantId')
            ->setParameter('distantId', $value);
    }

    #[\Override]
    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
