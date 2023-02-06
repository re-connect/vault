<?php

namespace App\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SolutionExtension extends AbstractExtension
{
    public const RECONNECT_FR = 'RECONNECT.FR';
    public const RECONNECT_SOCIAL = 'RECONNECT.SOCIAL';
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getCurrentSolution', [$this, 'getCurrentSolution']),
        ];
    }

    public function getCurrentSolution()
    {
        $host = $this->requestStack->getCurrentRequest()->getHost();
        if (preg_match('#social#', $host)) {
            return self::RECONNECT_SOCIAL;
        }

        return self::RECONNECT_FR;
    }

    public function getName(): string
    {
        return 're.twig.solution';
    }
}
