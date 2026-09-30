<?php

namespace App\Tests\Factory;

use App\Entity\Client;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Client                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Client                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Client                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Client                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Client                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Client                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Client                                                                                     random(array $attributes = [])
 * @method static \App\Entity\Client                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Client[]                                                                                   all()
 * @method static \App\Entity\Client[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Client[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Client[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\Client[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Client[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Client, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Client create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Client createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Client find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Client findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Client first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Client last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Client random(array $attributes = [])
 * @phpstan-method static \App\Entity\Client randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Client> all()
 * @phpstan-method static list<\App\Entity\Client> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Client> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Client> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Client> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Client> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Client> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Client>
 */
final class ClientFactory extends PersistentObjectFactory
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
