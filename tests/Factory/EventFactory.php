<?php

namespace App\Tests\Factory;

use App\Entity\Evenement;

/**
 * @method        \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          create(array|callable $attributes = [])
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          createOne(array $attributes = [])
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          find(object|array|mixed $criteria)
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          findOrCreate(array $attributes)
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          first(string $sortedField = 'id')
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          last(string $sortedField = 'id')
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          random(array $attributes = [])
 * @method static \App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy                                                          randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      all()
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      findBy(array $attributes)
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Evenement[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                      randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy>                    many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Evenement|\Zenstruck\Foundry\Persistence\Proxy>                    sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Evenement, \App\Repository\EvenementRepository> repository()
 *
 * @phpstan-method \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> all()
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Evenement&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Evenement>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Evenement>
 */
class EventFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
            'nom' => self::faker()->text(),
            'date' => new \DateTime('tomorrow'),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
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
        return Evenement::class;
    }
}
