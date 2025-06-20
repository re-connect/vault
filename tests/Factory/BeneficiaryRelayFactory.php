<?php

namespace App\Tests\Factory;

use App\Entity\BeneficiaireCentre;
use App\Repository\BeneficiaireCentreRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<BeneficiaireCentre>
 *
 * @method        BeneficiaireCentre|Proxy                     create(array|callable $attributes = [])
 * @method static BeneficiaireCentre|Proxy                     createOne(array $attributes = [])
 * @method static BeneficiaireCentre|Proxy                     find(object|array|mixed $criteria)
 * @method static BeneficiaireCentre|Proxy                     findOrCreate(array $attributes)
 * @method static BeneficiaireCentre|Proxy                     first(string $sortedField = 'id')
 * @method static BeneficiaireCentre|Proxy                     last(string $sortedField = 'id')
 * @method static BeneficiaireCentre|Proxy                     random(array $attributes = [])
 * @method static BeneficiaireCentre|Proxy                     randomOrCreate(array $attributes = [])
 * @method static BeneficiaireCentreRepository|RepositoryProxy repository()
 * @method static BeneficiaireCentre[]|Proxy[]                 all()
 * @method static BeneficiaireCentre[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static BeneficiaireCentre[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static BeneficiaireCentre[]|Proxy[]                 findBy(array $attributes)
 * @method static BeneficiaireCentre[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static BeneficiaireCentre[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class BeneficiaryRelayFactory extends ModelFactory
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
    protected function getDefaults(): array
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
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(BeneficiaireCentre $beneficiaireCentre): void {})
        ;
    }

    protected static function getClass(): string
    {
        return BeneficiaireCentre::class;
    }
}
