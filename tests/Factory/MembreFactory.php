<?php

namespace App\Tests\Factory;

use App\Entity\Centre;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Membre>
 *
 * @method static Membre|Proxy                     createOne(array $attributes = [])
 * @method static Membre[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Membre[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Membre|Proxy                     find(object|array|mixed $criteria)
 * @method static Membre|Proxy                     findOrCreate(array $attributes)
 * @method static Membre|Proxy                     first(string $sortedField = 'id')
 * @method static Membre|Proxy                     last(string $sortedField = 'id')
 * @method static Membre|Proxy                     random(array $attributes = [])
 * @method static Membre|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Membre[]|Proxy[]                 all()
 * @method static Membre[]|Proxy[]                 findBy(array $attributes)
 * @method static Membre[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Membre[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static MembreRepository|RepositoryProxy repository()
 * @method        Membre|Proxy                     create(array|callable $attributes = [])
 */
final class MembreFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'user' => UserFactory::new(),
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
        return Membre::class;
    }

    public static function findByEmail(string $email): Membre|Proxy
    {
        return MembreFactory::find(['user' => UserFactory::find(['email' => $email])]);
    }

    /** @param array<Centre> $centres */
    public function linkToRelays(array $centres): self
    {
        return $this->addState(['membresCentres' => array_map(fn ($centre) => MembreCentreFactory::createOne(['membre' => $this, 'centre' => $centre]), $centres)]);
    }
}
