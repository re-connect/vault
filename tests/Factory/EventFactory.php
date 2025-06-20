<?php

namespace App\Tests\Factory;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Evenement>
 *
 * @method static Evenement|Proxy                     createOne(array $attributes = [])
 * @method static Evenement[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Evenement[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Evenement|Proxy                     find(object|array|mixed $criteria)
 * @method static Evenement|Proxy                     findOrCreate(array $attributes)
 * @method static Evenement|Proxy                     first(string $sortedField = 'id')
 * @method static Evenement|Proxy                     last(string $sortedField = 'id')
 * @method static Evenement|Proxy                     random(array $attributes = [])
 * @method static Evenement|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Evenement[]|Proxy[]                 all()
 * @method static Evenement[]|Proxy[]                 findBy(array $attributes)
 * @method static Evenement[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Evenement[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static EvenementRepository|RepositoryProxy repository()
 * @method        Evenement|Proxy                     create(array|callable $attributes = [])
 */
class EventFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'bPrive' => self::faker()->boolean(),
            'nom' => self::faker()->text(),
            'date' => new \DateTime('tomorrow'),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'beneficiaire' => BeneficiaireFactory::randomOrCreate()->object(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Contact $contact): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Evenement::class;
    }
}
