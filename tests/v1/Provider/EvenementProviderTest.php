<?php

namespace App\Tests\v1\Provider;

use App\Provider\EvenementProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EvenementProviderTest extends KernelTestCase
{
    private ?EvenementProvider $provider;

    public function testGetDueEvents()
    {
        $this->provider->getDueEvents();

        $this->assertEquals(1, 1);
    }

    protected function setUp(): void
    {
        $this->provider = self::getContainer()->get(EvenementProvider::class);
    }
}
