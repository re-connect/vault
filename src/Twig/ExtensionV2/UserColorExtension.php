<?php

namespace App\Twig\ExtensionV2;

use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserColorExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUserBackgroundColor', [$this, 'getUserBackgroundColor']),
            new TwigFunction('getUserButtonColor', [$this, 'getUserButtonColor']),
            new TwigFunction('getUserTextColor', [$this, 'getUserTextColor']),
            new TwigFunction('getUserThemeColor', [$this, 'getUserThemeColor']),
        ];
    }

    private function getUserColorClass(string $beneficiaryColor, string $proColor): string
    {
        if (!$user = $this->getUser()) {
            return '';
        }

        return $user->isBeneficiaire() ? $beneficiaryColor : $proColor;
    }

    public function getUserBackgroundColor(): string
    {
        return $this->getUserColorClass('bg-light-green', 'bg-light-blue');
    }

    public function getUserButtonColor(): string
    {
        return $this->getUserColorClass('bg-green text-white', 'bg-blue text-white');
    }

    public function getUserTextColor(): string
    {
        return $this->getUserColorClass('text-green', 'text-blue');
    }

    public function getUserThemeColor(): string
    {
        return $this->getUserColorClass('green', 'blue');
    }
}
