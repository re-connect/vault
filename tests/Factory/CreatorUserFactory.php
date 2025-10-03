<?php

namespace App\Tests\Factory;

use App\Entity\CreatorUser;
use App\Repository\CreatorUserRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CreatorUser>
 *
 * @method static CreatorUser|Proxy                     createOne(array $attributes = [])
 * @method static CreatorUser[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CreatorUser[]|Proxy[]                 createSequence(array|callable $sequence)
 * @method static CreatorUser|Proxy                     find(object|array|mixed $criteria)
 * @method static CreatorUser|Proxy                     findOrCreate(array $attributes)
 * @method static CreatorUser|Proxy                     first(string $sortedField = 'id')
 * @method static CreatorUser|Proxy                     last(string $sortedField = 'id')
 * @method static CreatorUser|Proxy                     random(array $attributes = [])
 * @method static CreatorUser|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CreatorUser[]|Proxy[]                 all()
 * @method static CreatorUser[]|Proxy[]                 findBy(array $attributes)
 * @method static CreatorUser[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static CreatorUser[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CreatorUserRepository|RepositoryProxy repository()
 * @method        CreatorUser|Proxy                     create(array|callable $attributes = [])
 */
final class CreatorUserFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'entity' => MembreFactory::randomOrCreate()->getUser(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CreatorUser $creatorUser): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CreatorUser::class;
    }
}
