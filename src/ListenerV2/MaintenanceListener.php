<?php

namespace App\ListenerV2;

use App\Checker\FeatureFlagChecker;
use App\Command\ToggleMaintenanceCommand;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        if (!$event->isMainRequest() || !$this->featureFlagChecker->isEnabled(ToggleMaintenanceCommand::FEATURE_FLAG_NAME)) {
            return;
        }

        $this->isApiRequest($event->getRequest()->getPathInfo())
            ? $event->setResponse(new JsonResponse(['error' => 'The service is temporarily unavailable.'], Response::HTTP_SERVICE_UNAVAILABLE))
            : $event->setResponse(new Response($this->environment->render('v2/maintenance/maintenance.html.twig')));
    }

    public function isApiRequest(string $requestPathInfo): bool
    {
        return str_starts_with($requestPathInfo, '/api') || str_starts_with($requestPathInfo, '/oauth');
    }
}
