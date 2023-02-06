<?php

namespace App\Tests\Factory;

use App\Entity\MembreCentre;
use App\Repository\MembreCentreRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<MembreCentre>
 *
 * @method static MembreCentre|Proxy                     createOne(array $attributes = [])
 * @method static MembreCentre[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static MembreCentre[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static MembreCentre|Proxy                     find(object|array|mixed $criteria)
 * @method static MembreCentre|Proxy                     findOrCreate(array $attributes)
 * @method static MembreCentre|Proxy                     first(string $sortedField = 'id')
 * @method static MembreCentre|Proxy                     last(string $sortedField = 'id')
 * @method static MembreCentre|Proxy                     random(array $attributes = [])
 * @method static MembreCentre|Proxy                     randomOrCreate(array $attributes = [])
 * @method static MembreCentre[]|Proxy[]                 all()
 * @method static MembreCentre[]|Proxy[]                 findBy(array $attributes)
 * @method static MembreCentre[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static MembreCentre[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static MembreCentreRepository|RepositoryProxy repository()
 * @method        MembreCentre|Proxy                     create(array|callable $attributes = [])
 */
final class MembreCentreFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
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

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(MembreCentre $membreCentre): void {})
        ;
    }

    protected static function getClass(): string
    {
        return MembreCentre::class;
    }
}
