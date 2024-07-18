<?php

namespace App\Api\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Note;
use App\Security\HelperV2\Oauth2Helper;
use Doctrine\ORM\QueryBuilder;

final readonly class ClientResourceCollectionExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const array HANDLED_CLASSES = [
        Beneficiaire::class,
        Document::class,
        Note::class,
    ];

    public function __construct(private Oauth2Helper $oauth2Helper)
    {
    }

    #[\Override]
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addClientExternalLinksFilter($queryBuilder, $resourceClass);
    }

    #[\Override]
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addClientExternalLinksFilter($queryBuilder, $resourceClass);
    }

    private function addClientExternalLinksFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $rootAliases = $queryBuilder->getRootAliases();
        if (
            $this->oauth2Helper->isClientGrantedAllPrivileges()
            || !in_array($resourceClass, self::HANDLED_CLASSES)
            || 0 === count($rootAliases)
        ) {
            return;
        }
        $rootAlias = $rootAliases[0];
        $beneficiaryAlias = $rootAlias;

        if (Document::class === $resourceClass) {
            $beneficiaryAlias = 'beneficiary';
            $queryBuilder->innerJoin(sprintf('%s.beneficiary', $rootAlias), $beneficiaryAlias);
        }

        if (Note::class === $resourceClass) {
            $beneficiaryAlias = 'beneficiaire';
            $queryBuilder->innerJoin(sprintf('%s.beneficiaire', $rootAlias), $beneficiaryAlias);
        }

        $queryBuilder
            ->innerJoin(sprintf('%s.externalLinks', $beneficiaryAlias), 'externalLink')
            ->innerJoin('externalLink.client', 'client')
            ->andWhere('client = :client')
            ->setParameter('client', $this->oauth2Helper->getClient());
    }
}
