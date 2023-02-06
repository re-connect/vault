<?php

namespace App\Tests\Factory;

use App\Entity\Association;
use App\Repository\AssociationRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Association>
 *
 * @method static Association|Proxy                     createOne(array $attributes = [])
 * @method static Association[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Association[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Association|Proxy                     find(object|array|mixed $criteria)
 * @method static Association|Proxy                     findOrCreate(array $attributes)
 * @method static Association|Proxy                     first(string $sortedField = 'id')
 * @method static Association|Proxy                     last(string $sortedField = 'id')
 * @method static Association|Proxy                     random(array $attributes = [])
 * @method static Association|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Association[]|Proxy[]                 all()
 * @method static Association[]|Proxy[]                 findBy(array $attributes)
 * @method static Association[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Association[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static AssociationRepository|RepositoryProxy repository()
 * @method        Association|Proxy                     create(array|callable $attributes = [])
 */
final class AssociationFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'nom' => self::faker()->name(),
            'categorieJuridique' => self::faker()->text(15),
            'siren' => self::faker()->randomNumber(9),
            'urlSite' => self::faker()->url(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Membre $membre): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Association::class;
    }
}
