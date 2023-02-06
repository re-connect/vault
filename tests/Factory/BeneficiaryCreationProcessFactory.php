<?php

namespace App\Tests\Factory;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\RepositoryV2\BeneficiaryCreationProcessRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<BeneficiaryCreationProcess>
 *
 * @method static BeneficiaryCreationProcess|Proxy                     createOne(array $attributes = [])
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static BeneficiaryCreationProcess|Proxy                     find(object|array|mixed $criteria)
 * @method static BeneficiaryCreationProcess|Proxy                     findOrCreate(array $attributes)
 * @method static BeneficiaryCreationProcess|Proxy                     first(string $sortedField = 'id')
 * @method static BeneficiaryCreationProcess|Proxy                     last(string $sortedField = 'id')
 * @method static BeneficiaryCreationProcess|Proxy                     random(array $attributes = [])
 * @method static BeneficiaryCreationProcess|Proxy                     randomOrCreate(array $attributes = [])
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 all()
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 findBy(array $attributes)
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static BeneficiaryCreationProcess[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static BeneficiaryCreationProcessRepository|RepositoryProxy repository()
 * @method        BeneficiaryCreationProcess|Proxy                     create(array|callable $attributes = [])
 */
final class BeneficiaryCreationProcessFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'isCreating' => true,
            'remotely' => false,
            'beneficiary' => BeneficiaireFactory::new(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(BeneficiaryCreationProcess $beneficiaryCreationProcess): void {})
        ;
    }

    protected static function getClass(): string
    {
        return BeneficiaryCreationProcess::class;
    }
}
