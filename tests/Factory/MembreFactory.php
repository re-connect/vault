<?php

namespace App\Tests\Factory;

use App\Entity\Centre;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @method        \App\Entity\Membre                                                                                       create(array|callable $attributes = [])
 * @method static \App\Entity\Membre                                                                                       createOne(array $attributes = [])
 * @method static \App\Entity\Membre                                                                                       find(object|array|mixed $criteria)
 * @method static \App\Entity\Membre                                                                                       findOrCreate(array $attributes)
 * @method static \App\Entity\Membre                                                                                       first(string $sortedField = 'id')
 * @method static \App\Entity\Membre                                                                                       last(string $sortedField = 'id')
 * @method static \App\Entity\Membre                                                                                       random(array $attributes = [])
 * @method static \App\Entity\Membre                                                                                       randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Membre[]                                                                                     all()
 * @method static \App\Entity\Membre[]                                                                                     createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Membre[]                                                                                     createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Membre[]                                                                                     findBy(array $attributes)
 * @method static \App\Entity\Membre[]                                                                                     randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Membre[]                                                                                     randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Membre>                                                 many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Membre>                                                 sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<\App\Entity\Membre, \App\Repository\MembreRepository> repository()
 *
 * @phpstan-method \App\Entity\Membre create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Membre createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Membre find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Membre findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Membre first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Membre last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Membre random(array $attributes = [])
 * @phpstan-method static \App\Entity\Membre randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Membre> all()
 * @phpstan-method static list<\App\Entity\Membre> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Membre> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Membre> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Membre> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Membre> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Membre> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Membre> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\Entity\Membre>
 */
final class MembreFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'createdAt' => new \DateTime('now'),
            'updatedAt' => new \DateTime('now'),
            'user' => UserFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Membre $membre): void {})
        ;
    }

    #[\Override]
    public static function class(): string
    {
        return Membre::class;
    }

    public static function findByEmail(string $email): Membre
    {
        return MembreFactory::find(['user' => UserFactory::find(['email' => $email])]);
    }

    /** @param array<Centre> $centres */
    public function linkToRelays(array $centres, bool $beneficiaryManagement = false, bool $proManagement = false): self
    {
        return $this->with([
            'membresCentres' => array_map(
                fn ($centre) => MembreCentreFactory::createOne([
                    'membre' => $this,
                    'centre' => $centre,
                    'droits' => [
                        MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => $beneficiaryManagement,
                        MembreCentre::MANAGE_PROS_PERMISSION => $proManagement,
                    ],
                ]), $centres
            ),
        ]);
    }
}
