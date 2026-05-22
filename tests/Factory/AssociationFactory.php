<?php

namespace App\Tests\Factory;

use App\Entity\Association;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @method        \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Association[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Association|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Association, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> all()
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Association&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Association>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Association>
 */
final class AssociationFactory extends PersistentProxyObjectFactory
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
            'categorieJuridique' => self::faker()->text(15),
            'siren' => self::faker()->randomNumber(9),
            'urlSite' => self::faker()->url(),
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
        return Association::class;
    }
}
