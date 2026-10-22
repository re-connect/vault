<?php

namespace App\Tests\Factory;

use App\Entity\Region;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Region                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Region                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Region                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Region                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Region                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Region                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Region                                                                                     random(array $attributes = [])
 * @method static \App\Entity\Region                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Region[]                                                                                   all()
 * @method static \App\Entity\Region[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Region[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Region[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\Region[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Region[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Region, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Region create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Region createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Region find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Region findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Region first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Region last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Region random(array $attributes = [])
 * @phpstan-method static \App\Entity\Region randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Region> all()
 * @phpstan-method static list<\App\Entity\Region> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Region> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Region> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Region> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Region> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Region> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Region>
 */
final class RegionFactory extends PersistentObjectFactory
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
