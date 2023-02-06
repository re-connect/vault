<?php

namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct($defaultLocale = 'fr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest($event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // doit être enregistré avant le Locale listener par défaut
            KernelEvents::REQUEST => [['onKernelRequest', 17]],
        ];
    }
}
