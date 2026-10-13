<?php

namespace App\Tests\v2\API;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractApiTestCase extends ApiTestCase
{
    use Factories;

    protected ?string $accessToken = null;

    protected const BASE_URL = '/api/v3';

    public function generateUrl(string $url): string
    {
        return sprintf('%s%s%saccess_token=%s', static::BASE_URL, $url, $this->getQueryDelimiter($url), $this->accessToken);
    }

    private function getQueryDelimiter(string $url): string
    {
        return str_contains($url, '?') ? '&' : '?';
    }
}
