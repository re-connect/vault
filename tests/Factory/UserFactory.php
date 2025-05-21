<?php

namespace App\Tests\Factory;

use App\Entity\Attributes\User;
use App\Repository\UserRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<User>
 *
 * @method static User|Proxy                     createOne(array $attributes = [])
 * @method static User[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static User[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static User|Proxy                     find(object|array|mixed $criteria)
 * @method static User|Proxy                     findOrCreate(array $attributes)
 * @method static User|Proxy                     first(string $sortedField = 'id')
 * @method static User|Proxy                     last(string $sortedField = 'id')
 * @method static User|Proxy                     random(array $attributes = [])
 * @method static User|Proxy                     randomOrCreate(array $attributes = [])
 * @method static User[]|Proxy[]                 all()
 * @method static User[]|Proxy[]                 findBy(array $attributes)
 * @method static User[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static User[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method        User|Proxy                     create(array|callable $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    public const WEAK_PASSWORD_HASH = '$2y$13$1e.Kr4Ru31eHQBKU3d6BY..EIerE6/IYA5K/JxMjGwBYb5dL7B6eG'; // = 'password'
    public const WEAK_PASSWORD_CLEAR = 'password';
    public const STRONG_PASSWORD_HASH = '$2y$13$te1UUDYPXELYC9jcVmil0.XQcmPValnWUN10VqDAJsh5zpnkiT9fm'; // = 'StrongPassword1!'
    public const STRONG_PASSWORD_CLEAR = 'StrongPassword1!';

    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
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

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this// ->afterInstantiate(function(User $user): void {})
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }

    public static function findByEmail(string $email): User|Proxy
    {
        return UserFactory::find(['email' => $email]);
    }
}
