<?php

namespace App\Tests\Factory;

use App\Entity\CreatorCentre;
use App\Repository\CreatorCentreRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CreatorCentre>
 *
 * @method static CreatorCentre|Proxy                     createOne(array $attributes = [])
 * @method static CreatorCentre[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CreatorCentre[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static CreatorCentre|Proxy                     find(object|array|mixed $criteria)
 * @method static CreatorCentre|Proxy                     findOrCreate(array $attributes)
 * @method static CreatorCentre|Proxy                     first(string $sortedField = 'id')
 * @method static CreatorCentre|Proxy                     last(string $sortedField = 'id')
 * @method static CreatorCentre|Proxy                     random(array $attributes = [])
 * @method static CreatorCentre|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CreatorCentre[]|Proxy[]                 all()
 * @method static CreatorCentre[]|Proxy[]                 findBy(array $attributes)
 * @method static CreatorCentre[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static CreatorCentre[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CreatorCentreRepository|RepositoryProxy repository()
 * @method        CreatorCentre|Proxy                     create(array|callable $attributes = [])
 */
final class CreatorCentreFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'entity' => RelayFactory::randomOrCreate(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CreatorCentre $creatorCentre): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CreatorCentre::class;
    }
}
