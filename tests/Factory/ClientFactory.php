<?php

namespace App\Tests\Factory;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use Zenstruck\Foundry\ModelFactory;

class ClientFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Client::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
