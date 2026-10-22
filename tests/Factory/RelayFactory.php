<?php

namespace App\Tests\Factory;

use App\Entity\Centre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Centre                                                                                       create(array|callable $attributes = [])
 * @method static \App\Entity\Centre                                                                                       createOne(array $attributes = [])
 * @method static \App\Entity\Centre                                                                                       find(object|array|mixed $criteria)
 * @method static \App\Entity\Centre                                                                                       findOrCreate(array $attributes)
 * @method static \App\Entity\Centre                                                                                       first(string $sortedField = 'id')
 * @method static \App\Entity\Centre                                                                                       last(string $sortedField = 'id')
 * @method static \App\Entity\Centre                                                                                       random(array $attributes = [])
 * @method static \App\Entity\Centre                                                                                       randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Centre[]                                                                                     all()
 * @method static \App\Entity\Centre[]                                                                                     createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Centre[]                                                                                     createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Centre[]                                                                                     findBy(array $attributes)
 * @method static \App\Entity\Centre[]                                                                                     randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Centre[]                                                                                     randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Centre>                                                 many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Centre>                                                 sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Centre, \App\Repository\CentreRepository> repository()
 *
 * @phpstan-method \App\Entity\Centre create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Centre createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Centre find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Centre findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Centre first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Centre last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Centre random(array $attributes = [])
 * @phpstan-method static \App\Entity\Centre randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Centre> all()
 * @phpstan-method static list<\App\Entity\Centre> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Centre> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Centre> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Centre> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Centre> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Centre> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Centre> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Centre>
 */
final class RelayFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'nom' => self::faker()->name(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Membre $membre): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Centre::class;
    }
}
