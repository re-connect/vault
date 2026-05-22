<?php

namespace App\Tests\Factory;

use App\Entity\Region;

/**
 * @method        \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Region[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Region, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> all()
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Region>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Region>
 */
final class RegionFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(25),
            'email' => self::faker()->email(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }

    #[\Override]
    public static function class(): string
    {
        return Region::class;
    }
}
