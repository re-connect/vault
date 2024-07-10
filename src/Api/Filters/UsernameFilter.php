<?php

namespace App\Api\Filters;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class UsernameFilter extends AbstractFilter
{
    #[\Override]
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('username' !== $property) {
            return;
        }

        $queryBuilder
            ->Where(sprintf('%s.username = :username', $queryBuilder->getRootAliases()[0]))
            ->setParameters([
                'username' => $value,
            ]);
    }

    #[\Override]
    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
