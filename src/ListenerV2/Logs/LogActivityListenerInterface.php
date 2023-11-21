<?php

namespace App\ListenerV2\Logs;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;

interface LogActivityListenerInterface
{
    public function postPersist(PostPersistEventArgs $args): void;

    public function preUpdate(PreUpdateEventArgs $args): void;

    public function preRemove(PreRemoveEventArgs $args): void;

    /** @param LifecycleEventArgs<ObjectManager> $args */
    public function log(LifecycleEventArgs $args, string $logType): void;
}
