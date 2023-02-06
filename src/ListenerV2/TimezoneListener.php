<?php

namespace App\ListenerV2;

use App\Entity\Evenement;
use App\Entity\Rappel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TimezoneListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $isValidEntity = $data instanceof Rappel || $data instanceof Evenement;

        if (!$isValidEntity || !$data->getDate() || !$data->getTimezone()) {
            return;
        }

        $date = $data->getDate();
        $timezone = $data->getTimezone();

        $data->setDate(new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone)));
        $event->setData($data);
    }
}
