<?php

namespace App\Tests\Factory;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @method        \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             create(array|callable $attributes = [])
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             createOne(array $attributes = [])
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             find(object|array|mixed $criteria)
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             findOrCreate(array $attributes)
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             first(string $sortedField = 'id')
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             last(string $sortedField = 'id')
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             random(array $attributes = [])
 * @method static \App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy                                                             randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         all()
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         findBy(array $attributes)
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Beneficiaire[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy>                       many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Beneficiaire|\Zenstruck\Foundry\Persistence\Proxy>                       sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Beneficiaire, \App\Repository\BeneficiaireRepository> repository()
 *
 * @phpstan-method \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> all()
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Beneficiaire&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Beneficiaire>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Beneficiaire>
 */
final class BeneficiaireFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'dateNaissance' => self::faker()->dateTime(),
            'neverClickedMesDocuments' => self::faker()->boolean(),
            'questionSecrete' => 'question',
            'reponseSecrete' => 'reponse',
            'lieuNaissance' => self::faker()->text(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'user' => UserFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Beneficiaire $beneficiaire): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Beneficiaire::class;
    }

    public static function findByEmail(string $email): Beneficiaire
    {
        return BeneficiaireFactory::find(['user' => UserFactory::find(['email' => $email])])->_real();
    }

    /** @param array<Centre> $centres */
    public function linkToRelays(array $centres): self
    {
        return $this->with(['beneficiairesCentres' => array_map(fn ($centre) => BeneficiaryRelayFactory::createOne(['beneficiaire' => $this, 'centre' => $centre]), $centres)]);
    }
}
