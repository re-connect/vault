<?php

namespace App\Tests\Factory;

use App\Entity\CreatorCentre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\CreatorCentre                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\CreatorCentre                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\CreatorCentre                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\CreatorCentre                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\CreatorCentre                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\CreatorCentre                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\CreatorCentre                                                                                     random(array $attributes = [])
 * @method static \App\Entity\CreatorCentre                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\CreatorCentre[]                                                                                   all()
 * @method static \App\Entity\CreatorCentre[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\CreatorCentre[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\CreatorCentre[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\CreatorCentre[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\CreatorCentre[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\CreatorCentre, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\CreatorCentre create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\CreatorCentre findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\CreatorCentre first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorCentre last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorCentre random(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre> all()
 * @phpstan-method static list<\App\Entity\CreatorCentre> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\CreatorCentre> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\CreatorCentre> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\CreatorCentre>
 */
final class CreatorCentreFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'entity' => RelayFactory::randomOrCreate(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CreatorCentre $creatorCentre): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return CreatorCentre::class;
    }
}
