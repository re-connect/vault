<?php

declare(strict_types=1);

namespace App\Twig\ExtensionV2;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConvertSecondsToDaysExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('seconds_to_days', $this->secondsToDays(...)),
        ];
    }

    public function secondsToDays(int $seconds, int $precision = 0): int
    {
        return (int) round($seconds / (60 * 60 * 24), $precision);
    }
}