<?php

namespace App\Tests\Factory;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Note>
 *
 * @method static Note|Proxy                     createOne(array $attributes = [])
 * @method static Note[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Note[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static Note|Proxy                     find(object|array|mixed $criteria)
 * @method static Note|Proxy                     findOrCreate(array $attributes)
 * @method static Note|Proxy                     first(string $sortedField = 'id')
 * @method static Note|Proxy                     last(string $sortedField = 'id')
 * @method static Note|Proxy                     random(array $attributes = [])
 * @method static Note|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Note[]|Proxy[]                 all()
 * @method static Note[]|Proxy[]                 findBy(array $attributes)
 * @method static Note[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Note[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static NoteRepository|RepositoryProxy repository()
 * @method        Note|Proxy                     create(array|callable $attributes = [])
 */
class NoteFactory extends ModelFactory
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
            'contenu' => self::faker()->text(),
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
        return Note::class;
    }
}
