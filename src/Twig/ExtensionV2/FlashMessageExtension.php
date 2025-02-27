<?php

namespace App\Twig\ExtensionV2;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FlashMessageExtension extends AbstractExtension
{
    private const string TYPE_SUCCESS = 'success';
    private const string TYPE_ERROR = 'error';
    private const string TYPE_DANGER = 'danger';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getFlashMessageColor', $this->getFlashMessageColor(...)),
            new TwigFunction('getFlashMessageTitle', $this->getFlashMessageTitle(...)),
        ];
    }

    public function getFlashMessageColor(string $flashType): string
    {
        return match ($flashType) {
            self::TYPE_SUCCESS => 'bg-green',
            self::TYPE_ERROR, self::TYPE_DANGER => 'bg-red',
            default => '',
        };
    }

    public function getFlashMessageTitle(string $flashType): string
    {
        return match ($flashType) {
            self::TYPE_SUCCESS => $this->translator->trans('success'),
            self::TYPE_ERROR, self::TYPE_DANGER => $this->translator->trans('error'),
            default => '',
        };
    }
}
