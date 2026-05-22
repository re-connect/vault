<?php

namespace App\Tests\Factory;

use App\Entity\MembreCentre;

/**
 * @method        \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\MembreCentre[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\MembreCentre, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> random(array $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> all()
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\MembreCentre>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\MembreCentre>
 */
final class MembreCentreFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'bValid' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
            'membre' => MembreFactory::new(),
            'centre' => RelayFactory::new(),
            'droits' => [],
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(MembreCentre $membreCentre): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return MembreCentre::class;
    }
}
