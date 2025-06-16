<?php

namespace App\Api\Filters;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Attributes\Dossier;
use Doctrine\ORM\QueryBuilder;

class FolderIdFilter extends AbstractFilter
{
    #[\Override]
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('folderId' !== $property) {
            return;
        }

        $folderProperty = Dossier::class === $resourceClass ? 'dossierParent' : 'dossier';

        if ('root' === $value) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s is null', $queryBuilder->getRootAliases()[0], $folderProperty));

            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.%s = :folderId', $queryBuilder->getRootAliases()[0], $folderProperty))
            ->setParameter('folderId', $value);
    }

    #[\Override]
    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
