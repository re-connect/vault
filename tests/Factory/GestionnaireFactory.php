<?php

namespace App\Tests\Factory;

use App\Entity\Gestionnaire;
use App\Repository\GestionnaireRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Gestionnaire>
 *
 * @method static Gestionnaire|Proxy                     createOne(array $attributes = [])
 * @method static Gestionnaire[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Gestionnaire[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Gestionnaire|Proxy                     find(object|array|mixed $criteria)
 * @method static Gestionnaire|Proxy                     findOrCreate(array $attributes)
 * @method static Gestionnaire|Proxy                     first(string $sortedField = 'id')
 * @method static Gestionnaire|Proxy                     last(string $sortedField = 'id')
 * @method static Gestionnaire|Proxy                     random(array $attributes = [])
 * @method static Gestionnaire|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Gestionnaire[]|Proxy[]                 all()
 * @method static Gestionnaire[]|Proxy[]                 findBy(array $attributes)
 * @method static Gestionnaire[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Gestionnaire[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static GestionnaireRepository|RepositoryProxy repository()
 * @method        Gestionnaire|Proxy                     create(array|callable $attributes = [])
 */
final class GestionnaireFactory extends ModelFactory
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
            'association' => AssociationFactory::new(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this// ->afterInstantiate(function(Membre $membre): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Gestionnaire::class;
    }

    public static function findByEmail(string $email): Gestionnaire|Proxy
    {
        $user = UserFactory::find(['email' => $email]);

        return GestionnaireFactory::find(['user' => $user]);
    }
}
