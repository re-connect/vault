<?php

namespace App\Tests\Factory;

use App\Entity\Document;

/**
 * @method        \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         create(array|callable $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         createOne(array $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         find(object|array|mixed $criteria)
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         findOrCreate(array $attributes)
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         first(string $sortedField = 'id')
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         last(string $sortedField = 'id')
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         random(array $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     all()
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     findBy(array $attributes)
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy>                   many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy>                   sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Document, \App\Repository\DocumentRepository> repository()
 *
 * @phpstan-method \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> all()
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Document>
 */
class DocumentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'bPrive' => self::faker()->boolean(),
            'nom' => self::faker()->text(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'objectKey' => self::faker()->text(),
            'extension' => self::faker()->fileExtension(),
            'taille' => self::faker()->numberBetween(0, 200000),
            'beneficiaire' => BeneficiaireFactory::randomOrCreate()->_real(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Contact $contact): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Document::class;
    }
}
