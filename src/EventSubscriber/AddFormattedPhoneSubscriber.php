<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AddFormattedPhoneSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $user = $event->getData();

        if (!$user) {
            return;
        }

        // If the form contains an intl-form-input field, it will have an additional 'formatted-number' field
        // We get its data, and hydrate it into the 'telephone' field
        if (isset($user['formatted-number'])) {
            $user['telephone'] = $user['formatted-number'];
            unset($user['formatted-number']);
            $event->setData($user);
        }
    }
}
