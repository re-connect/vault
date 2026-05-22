<?php

namespace App\Tests\Factory;

use App\Entity\ResetPasswordRequest;

/**
 * @method        \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       create(array|callable $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       createOne(array $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       find(object|array|mixed $criteria)
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       findOrCreate(array $attributes)
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       first(string $sortedField = 'id')
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       last(string $sortedField = 'id')
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       random(array $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy                                                                       randomOrCreate(array $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   all()
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   findBy(array $attributes)
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\ResetPasswordRequest[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy>                                 many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\ResetPasswordRequest|\Zenstruck\Foundry\Persistence\Proxy>                                 sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\ResetPasswordRequest, \App\RepositoryV2\ResetPasswordRequestRepository> repository()
 *
 * @phpstan-method \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> random(array $attributes = [])
 * @phpstan-method static \App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> all()
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\ResetPasswordRequest&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\ResetPasswordRequest>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\ResetPasswordRequest>
 */
final class ResetPasswordRequestFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    #[\Override]
    protected function defaults(): array
    {
        return [
            'expiresAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'hashedToken' => self::faker()->text(100),
            'requestedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'selector' => self::faker()->text(20),
            'user' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ResetPasswordRequest $resetPasswordRequest): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return ResetPasswordRequest::class;
    }
}
