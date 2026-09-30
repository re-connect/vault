<?php

namespace App\Tests\Factory;

use App\Entity\Document;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Document                                                                                         create(array|callable $attributes = [])
 * @method static \App\Entity\Document                                                                                         createOne(array $attributes = [])
 * @method static \App\Entity\Document                                                                                         find(object|array|mixed $criteria)
 * @method static \App\Entity\Document                                                                                         findOrCreate(array $attributes)
 * @method static \App\Entity\Document                                                                                         first(string $sortedField = 'id')
 * @method static \App\Entity\Document                                                                                         last(string $sortedField = 'id')
 * @method static \App\Entity\Document                                                                                         random(array $attributes = [])
 * @method static \App\Entity\Document                                                                                         randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Document[]                                                                                       all()
 * @method static \App\Entity\Document[]                                                                                       createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Document[]                                                                                       createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Document[]                                                                                       findBy(array $attributes)
 * @method static \App\Entity\Document[]                                                                                       randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Document[]                                                                                       randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document>                                                   many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document>                                                   sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Document, \App\Repository\DocumentRepository> repository()
 *
 * @phpstan-method \App\Entity\Document create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Document createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Document find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Document findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Document first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document random(array $attributes = [])
 * @phpstan-method static \App\Entity\Document randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document> all()
 * @phpstan-method static list<\App\Entity\Document> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Document> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Document> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Document> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Document>
 */
class DocumentFactory extends PersistentObjectFactory
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
