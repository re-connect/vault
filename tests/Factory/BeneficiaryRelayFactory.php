<?php

namespace App\Tests\Factory;

use App\Entity\BeneficiaireCentre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\BeneficiaireCentre                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\BeneficiaireCentre                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\BeneficiaireCentre                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\BeneficiaireCentre                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\BeneficiaireCentre                                                                                     random(array $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   all()
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\BeneficiaireCentre[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaireCentre>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaireCentre>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\BeneficiaireCentre, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\BeneficiaireCentre create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaireCentre createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaireCentre find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\BeneficiaireCentre findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\BeneficiaireCentre first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\BeneficiaireCentre last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\BeneficiaireCentre random(array $attributes = [])
 * @phpstan-method static \App\Entity\BeneficiaireCentre randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> all()
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\BeneficiaireCentre> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaireCentre> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\BeneficiaireCentre> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\BeneficiaireCentre>
 */
final class BeneficiaryRelayFactory extends PersistentObjectFactory
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
            'bValid' => true,
            'beneficiaire' => BeneficiaireFactory::new(),
            'centre' => RelayFactory::new(),
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(BeneficiaireCentre $beneficiaireCentre): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return BeneficiaireCentre::class;
    }
}
