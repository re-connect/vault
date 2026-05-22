<?php

namespace App\Tests\Factory;

use App\Entity\Note;

/**
 * @method        \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Note[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Note, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> all()
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Note&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Note>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Note>
 */
class NoteFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
            'beneficiaire' => BeneficiaireFactory::randomOrCreate()->_real(),
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
