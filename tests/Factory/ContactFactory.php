<?php

namespace App\Tests\Factory;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Contact>
 *
 * @method static Contact|Proxy                     createOne(array $attributes = [])
 * @method static Contact[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Contact[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Contact|Proxy                     find(object|array|mixed $criteria)
 * @method static Contact|Proxy                     findOrCreate(array $attributes)
 * @method static Contact|Proxy                     first(string $sortedField = 'id')
 * @method static Contact|Proxy                     last(string $sortedField = 'id')
 * @method static Contact|Proxy                     random(array $attributes = [])
 * @method static Contact|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Contact[]|Proxy[]                 all()
 * @method static Contact[]|Proxy[]                 findBy(array $attributes)
 * @method static Contact[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Contact[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static ContactRepository|RepositoryProxy repository()
 * @method        Contact|Proxy                     create(array|callable $attributes = [])
 */
final class ContactFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'bPrive' => self::faker()->boolean(),
            'nom' => self::faker()->lastName(),
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'prenom' => self::faker()->firstName(),
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
        return Contact::class;
    }
}
