<?php

namespace App\Tests\Factory;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Repository\BeneficiaireRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Beneficiaire>
 *
 * @method static Beneficiaire|Proxy                     createOne(array $attributes = [])
 * @method static Beneficiaire[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Beneficiaire[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Beneficiaire|Proxy                     find(object|array|mixed $criteria)
 * @method static Beneficiaire|Proxy                     findOrCreate(array $attributes)
 * @method static Beneficiaire|Proxy                     first(string $sortedField = 'id')
 * @method static Beneficiaire|Proxy                     last(string $sortedField = 'id')
 * @method static Beneficiaire|Proxy                     random(array $attributes = [])
 * @method static Beneficiaire|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Beneficiaire[]|Proxy[]                 all()
 * @method static Beneficiaire[]|Proxy[]                 findBy(array $attributes)
 * @method static Beneficiaire[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Beneficiaire[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static BeneficiaireRepository|RepositoryProxy repository()
 * @method        Beneficiaire|Proxy                     create(array|callable $attributes = [])
 */
final class BeneficiaireFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'totalFileSize' => self::faker()->randomNumber(),
            'dateNaissance' => self::faker()->dateTime(),
            'isCreating' => false,
            'neverClickedMesDocuments' => self::faker()->boolean(),
            'questionSecrete' => 'question',
            'reponseSecrete' => 'reponse',
            'lieuNaissance' => self::faker()->text(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'user' => UserFactory::new(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Beneficiaire $beneficiaire): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Beneficiaire::class;
    }

    public static function findByEmail(string $email): Beneficiaire|Proxy
    {
        return BeneficiaireFactory::find(['user' => UserFactory::find(['email' => $email])]);
    }

    /** @param array<Centre> $centres */
    public function linkToRelays(array $centres): self
    {
        return $this->addState(['beneficiairesCentres' => array_map(fn ($centre) => BeneficiaryRelayFactory::createOne(['beneficiaire' => $this, 'centre' => $centre]), $centres)]);
    }
}
