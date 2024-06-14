<?php

namespace App\Twig\ExtensionV2;

use App\ServiceV2\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [new TwigFunction('getUserNotifications', $this->getUserNotifications(...))];
    }

    public function getUserNotifications(): array
    {
        return $this->service->getUserNotifications();
    }
}
