<?php

namespace App\Tests\Factory;

use App\Entity\CreatorCentre;

/**
 * @method        \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\CreatorCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\CreatorCentre, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> random(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> all()
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorCentre>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\CreatorCentre>
 */
final class CreatorCentreFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
