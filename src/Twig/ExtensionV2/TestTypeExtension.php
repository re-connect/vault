<?php

namespace App\Twig\ExtensionV2;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TestTypeExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            'instanceof' => new TwigTest('instanceof', [$this, 'isInstanceOf']),
        ];
    }

    public function isInstanceof($var, $instance): bool
    {
        return $var instanceof $instance;
    }
}
