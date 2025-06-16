<?php

namespace App\ListenerV2;

use App\Entity\Attributes\Evenement;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TimezoneListener implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (!$data instanceof Evenement || !$data->getTimezone()) {
            return;
        }

        $date = $data->getDate();
        $timezone = $data->getTimezone();

        $data->setDate(new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone)));
        $event->setData($data);
    }
}
