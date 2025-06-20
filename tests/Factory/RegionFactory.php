<?php

namespace App\Tests\Factory;

use App\Entity\Region;
use App\Repository\RegionRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Region>
 *
 * @method        Region|Proxy                     create(array|callable $attributes = [])
 * @method static Region|Proxy                     createOne(array $attributes = [])
 * @method static Region|Proxy                     find(object|array|mixed $criteria)
 * @method static Region|Proxy                     findOrCreate(array $attributes)
 * @method static Region|Proxy                     first(string $sortedField = 'id')
 * @method static Region|Proxy                     last(string $sortedField = 'id')
 * @method static Region|Proxy                     random(array $attributes = [])
 * @method static Region|Proxy                     randomOrCreate(array $attributes = [])
 * @method static RegionRepository|RepositoryProxy repository()
 * @method static Region[]|Proxy[]                 all()
 * @method static Region[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Region[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Region[]|Proxy[]                 findBy(array $attributes)
 * @method static Region[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Region[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class RegionFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(25),
            'email' => self::faker()->email(),
        ];
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return Region::class;
    }
}
