<?php

namespace App\Manager;

use App\Event\BeneficiaireEvent;
use App\Event\CentreEvent;
use App\Event\DonneePersonnelleEvent;
use App\Event\EvenementEvent;
use App\Event\GestionnaireEvent;
use App\Event\MembreEvent;
use App\Event\UserEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LogManager
{
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function onDonneePersonnelleEvent(DonneePersonnelleEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }

    public function getCurrentIp(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if (null !== $request) {
            return $request->getClientIp();
        }

        return '0.0.0.0';
    }

    public function onEvenementEvent(EvenementEvent $event): void
    {
        $this->logger->info(sprintf('(%s): ', $this->getCurrentIp()).$event);
    }

    public function onBeneficiaireEvent(BeneficiaireEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }

    public function onMembreEvent(MembreEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }

    public function onGestionnaireEvent(GestionnaireEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }

    public function onCentreEvent(CentreEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }

    public function onUserEvent(UserEvent $event): void
    {
        $this->logger->info('('.$this->getCurrentIp().'): '.$event, $event->getContext());
    }
}
