<?php

namespace App\Tests\Factory;

use App\Entity\Client;

/**
 * @method        \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Client[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Client, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> all()
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Client>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Client>
 */
final class ClientFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'access' => [],
            'actif' => true,
            'allowedGrantTypes' => ['client_credentials'],
            'randomId' => self::faker()->text(255),
            'redirectUris' => [],
            'secret' => self::faker()->text(255),
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
        return Client::class;
    }
}
