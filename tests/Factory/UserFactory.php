<?php

namespace App\Tests\Factory;

use App\Entity\User;

/**
 * @method        \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\User, \App\Repository\UserRepository> repository()
 *
 * @phpstan-method \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> random(array $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> all()
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\User>
 */
final class UserFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public const string WEAK_PASSWORD_HASH = '$2y$13$1e.Kr4Ru31eHQBKU3d6BY..EIerE6/IYA5K/JxMjGwBYb5dL7B6eG'; // = 'password'
    public const string WEAK_PASSWORD_CLEAR = 'password';
    public const string STRONG_PASSWORD_HASH = '$2y$13$te1UUDYPXELYC9jcVmil0.XQcmPValnWUN10VqDAJsh5zpnkiT9fm'; // = 'StrongPassword1!'
    public const string STRONG_PASSWORD_CLEAR = 'StrongPassword1!';

    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'username' => null,
            'email' => self::faker()->email(),
            'enabled' => true,
            'disabledBy' => null,
            'disabledAt' => null,
            'plainPassword' => self::STRONG_PASSWORD_CLEAR,
            'password' => self::STRONG_PASSWORD_HASH,
            'roles' => [],
            'firstVisit' => false,
            'bFirstMobileConnexion' => self::faker()->boolean(),
            'bActif' => true,
            'typeUser' => User::USER_TYPE_BENEFICIAIRE,
            'lastIp' => self::faker()->text(),
            'lastLang' => 'fr',
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'test' => self::faker()->boolean(),
            'canada' => false,
            'passwordUpdatedAt' => new \DateTimeImmutable('now'),
            'prenom' => self::faker()->firstName(),
            'nom' => self::faker()->lastName(),
            'telephone' => self::faker()->phoneNumber(),
            'hasPasswordWithLatestPolicy' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this// ->afterInstantiate(function(User $user): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    public static function findByEmail(string $email): User
    {
        return UserFactory::find(['email' => $email])->_real();
    }
}
