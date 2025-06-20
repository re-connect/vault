<?php

namespace App\ListenerV2\Logs;

use App\Domain\PushNotification\Notificator;
use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
readonly class PushNotificationListener
{
    public function __construct(private Notificator $notificator)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();
        if ($object instanceof Document) {
            $this->notificator->sendDocumentAddedNotification($object);
        }
    }
}
