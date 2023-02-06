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
        ];
    }

    public function getUserBackgroundColor(): string
    {
        $user = $this->getUser();

        if ($user) {
            return $user->isBeneficiaire() ? 'bg-light-green' : 'bg-light-blue';
        }

        return '';
    }

    public function getUserButtonColor(): string
    {
        $user = $this->getUser();

        if ($user) {
            return $user->isBeneficiaire() ? 'bg-green' : 'bg-blue';
        }

        return '';
    }
}
