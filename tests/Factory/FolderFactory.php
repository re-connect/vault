<?php

namespace App\Tests\Factory;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Dossier>
 *
 * @method static Dossier|Proxy                     createOne(array $attributes = [])
 * @method static Dossier[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Dossier[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Dossier|Proxy                     find(object|array|mixed $criteria)
 * @method static Dossier|Proxy                     findOrCreate(array $attributes)
 * @method static Dossier|Proxy                     first(string $sortedField = 'id')
 * @method static Dossier|Proxy                     last(string $sortedField = 'id')
 * @method static Dossier|Proxy                     random(array $attributes = [])
 * @method static Dossier|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Dossier[]|Proxy[]                 all()
 * @method static Dossier[]|Proxy[]                 findBy(array $attributes)
 * @method static Dossier[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Dossier[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static DossierRepository|RepositoryProxy repository()
 * @method        Dossier|Proxy                     create(array|callable $attributes = [])
 */
class FolderFactory extends ModelFactory
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
        return Dossier::class;
    }
}
