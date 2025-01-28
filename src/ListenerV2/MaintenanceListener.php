<?php

namespace App\ListenerV2;

use App\Checker\FeatureFlagChecker;
use App\Command\ToggleMaintenanceCommand;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 500)]
readonly class MaintenanceListener
{
    public function __construct(private Environment $environment, private FeatureFlagChecker $featureFlagChecker, private TranslatorInterface $translator)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->featureFlagChecker->isEnabled(ToggleMaintenanceCommand::FEATURE_FLAG_NAME)) {
            return;
        }

        str_starts_with($event->getRequest()->getPathInfo(), '/api')
            ? $event->setResponse(new JsonResponse(['message' => $this->translator->trans('maintenance')], Response::HTTP_SERVICE_UNAVAILABLE))
            : $event->setResponse(new Response($this->environment->render('v2/maintenance/maintenance.html.twig')));
    }
}
