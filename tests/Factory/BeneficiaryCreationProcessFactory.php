<?php

namespace App\Tests\Factory;

use App\Entity\BeneficiaryCreationProcess;

/**
 * @method        \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             create(array|callable $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             createOne(array $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             find(object|array|mixed $criteria)
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             findOrCreate(array $attributes)
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             first(string $sortedField = 'id')
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             last(string $sortedField = 'id')
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             random(array $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy                                                                             randomOrCreate(array $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         all()
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         createSequence(iterable|callable $sequence)
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         findBy(array $attributes)
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\BeneficiaryCreationProcess[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                         randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy>                                       many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaryCreationProcess|\Zenstruck\Foundry\Persistence\Proxy>                                       sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\BeneficiaryCreationProcess, \App\RepositoryV2\BeneficiaryCreationProcessRepository> repository()
 *
 * @phpstan-method \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> random(array $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> all()
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaryCreationProcess&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\BeneficiaryCreationProcess>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\BeneficiaryCreationProcess>
 */
final class BeneficiaryCreationProcessFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'isCreating' => true,
            'remotely' => false,
            'beneficiary' => BeneficiaireFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(BeneficiaryCreationProcess $beneficiaryCreationProcess): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return BeneficiaryCreationProcess::class;
    }
}
