<?php

namespace App\Tests\Factory;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Document>
 *
 * @method static Document|Proxy                     createOne(array $attributes = [])
 * @method static Document[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Document[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Document|Proxy                     find(object|array|mixed $criteria)
 * @method static Document|Proxy                     findOrCreate(array $attributes)
 * @method static Document|Proxy                     first(string $sortedField = 'id')
 * @method static Document|Proxy                     last(string $sortedField = 'id')
 * @method static Document|Proxy                     random(array $attributes = [])
 * @method static Document|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Document[]|Proxy[]                 all()
 * @method static Document[]|Proxy[]                 findBy(array $attributes)
 * @method static Document[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Document[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static DocumentRepository|RepositoryProxy repository()
 * @method        Document|Proxy                     create(array|callable $attributes = [])
 */
class DocumentFactory extends ModelFactory
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
            'objectKey' => self::faker()->text(),
            'extension' => self::faker()->fileExtension(),
            'taille' => self::faker()->numberBetween(0, 200000),
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
        return Document::class;
    }
}
