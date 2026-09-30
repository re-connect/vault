<?php

namespace App\Tests\Factory;

use App\Entity\MembreCentre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\MembreCentre                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\MembreCentre                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\MembreCentre                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\MembreCentre                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\MembreCentre                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\MembreCentre                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\MembreCentre                                                                                     random(array $attributes = [])
 * @method static \App\Entity\MembreCentre                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\MembreCentre[]                                                                                   all()
 * @method static \App\Entity\MembreCentre[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\MembreCentre[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\MembreCentre[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\MembreCentre[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\MembreCentre[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\MembreCentre, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\MembreCentre create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\MembreCentre findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\MembreCentre first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\MembreCentre last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\MembreCentre random(array $attributes = [])
 * @phpstan-method static \App\Entity\MembreCentre randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre> all()
 * @phpstan-method static list<\App\Entity\MembreCentre> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\MembreCentre> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\MembreCentre> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\MembreCentre> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\MembreCentre> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\MembreCentre>
 */
final class MembreCentreFactory extends PersistentObjectFactory
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
