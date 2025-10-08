<?php

namespace App\Tests\Factory;

use App\Entity\Centre;
use App\Repository\CentreRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Centre>
 *
 * @method static Centre|Proxy                     createOne(array $attributes = [])
 * @method static Centre[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Centre[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Centre|Proxy                     find(object|array|mixed $criteria)
 * @method static Centre|Proxy                     findOrCreate(array $attributes)
 * @method static Centre|Proxy                     first(string $sortedField = 'id')
 * @method static Centre|Proxy                     last(string $sortedField = 'id')
 * @method static Centre|Proxy                     random(array $attributes = [])
 * @method static Centre|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Centre[]|Proxy[]                 all()
 * @method static Centre[]|Proxy[]                 findBy(array $attributes)
 * @method static Centre[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Centre[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CentreRepository|RepositoryProxy repository()
 * @method        Centre|Proxy                     create(array|callable $attributes = [])
 */
final class RelayFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'nom' => self::faker()->name(),
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
        return Centre::class;
    }
}
