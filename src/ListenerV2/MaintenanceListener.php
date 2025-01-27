<?php

namespace App\ListenerV2;

use App\Checker\FeatureFlagChecker;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 500)]
readonly class MaintenanceListener
{
    public function __construct(private Environment $environment, private FeatureFlagChecker $featureFlagChecker)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->featureFlagChecker->isEnabled('maintenance')) {
            $event->setResponse(new Response($this->environment->render('v2/maintenance/maintenance.html.twig')));
        }
    }
}
