<?php

namespace App\Tests\Factory;

use App\Entity\CreatorUser;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @method        \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\CreatorUser[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorUser|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\CreatorUser, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> random(array $attributes = [])
 * @phpstan-method static \App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> all()
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\CreatorUser&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\CreatorUser>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\CreatorUser>
 */
final class CreatorUserFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'entity' => MembreFactory::randomOrCreate()->getUser(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CreatorUser $creatorUser): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return CreatorUser::class;
    }
}
