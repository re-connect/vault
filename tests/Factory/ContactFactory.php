<?php

namespace App\Tests\Factory;

use App\Entity\Contact;

/**
 * @method        \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Contact[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Contact|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Contact, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> all()
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Contact&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Contact>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Contact>
 */
final class ContactFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'bPrive' => self::faker()->boolean(),
            'nom' => self::faker()->lastName(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'prenom' => self::faker()->firstName(),
            'beneficiaire' => BeneficiaireFactory::randomOrCreate()->_real(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Contact $contact): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Contact::class;
    }
}
