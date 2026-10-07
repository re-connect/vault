<?php

namespace App\Tests\Factory;

use App\Entity\Note;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Note                                                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Note                                                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Note                                                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Note                                                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Note                                                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Note                                                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Note                                                                                     random(array $attributes = [])
 * @method static \App\Entity\Note                                                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Note[]                                                                                   all()
 * @method static \App\Entity\Note[]                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Note[]                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Note[]                                                                                   findBy(array $attributes)
 * @method static \App\Entity\Note[]                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Note[]                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note>                                               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note>                                               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Note, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Note create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Note createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Note find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Note findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Note first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Note last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Note random(array $attributes = [])
 * @phpstan-method static \App\Entity\Note randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Note> all()
 * @phpstan-method static list<\App\Entity\Note> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Note> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Note> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Note> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Note> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Note>
 */
class NoteFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'bPrive' => self::faker()->boolean(),
            'nom' => self::faker()->text(),
            'contenu' => self::faker()->text(),
            'updatedAt' => new \DateTime('now'),
            'beneficiaire' => BeneficiaireFactory::randomOrCreate(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Contact $contact): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Note::class;
    }
}
