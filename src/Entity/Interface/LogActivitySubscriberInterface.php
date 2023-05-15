<?php

namespace App\Entity\Interface;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

interface LogActivitySubscriberInterface
{
    public function postPersist(LifecycleEventArgs $args): void;

    public function preUpdate(PreUpdateEventArgs $args): void;

    public function preRemove(LifecycleEventArgs $args): void;

    public function log(LifecycleEventArgs $args, string $logType): void;
}
