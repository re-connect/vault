<?php

namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CommonExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('reDate', [$this, 'reDate']),
        ];
    }

    public function reDate($d, $format = '%d %B %Y'): string|array|null|false
    {
        setlocale(LC_TIME, 'fr_FR.ISO-8859-1', 'fra');

        if (is_string($d)) {
            $d = new \DateTime($d);
        }
        if ($d instanceof \DateTime) {
            $d = $d->getTimestamp();
        }
        setlocale(LC_TIME, 'fr_FR.ISO-8859-1', 'fra');

        return mb_convert_encoding(date($format, $d), 'UTF-8', 'ISO-8859-1');
    }
}
