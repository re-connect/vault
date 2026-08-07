<?php

namespace App\Tests\Factory;

use App\Entity\Dossier;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @method        \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        create(array|callable $attributes = [])
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        createOne(array $attributes = [])
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        find(object|array|mixed $criteria)
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        findOrCreate(array $attributes)
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        first(string $sortedField = 'id')
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        last(string $sortedField = 'id')
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        random(array $attributes = [])
 * @method static \App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy                                                        randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    all()
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    findBy(array $attributes)
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Dossier[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                    randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy>                  many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Dossier|\Zenstruck\Foundry\Persistence\Proxy>                  sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Dossier, \App\Repository\DossierRepository> repository()
 *
 * @phpstan-method \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> all()
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Dossier&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Dossier>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Dossier>
 */
class FolderFactory extends PersistentProxyObjectFactory
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
        return Dossier::class;
    }
}
