<?php

namespace App\EventSubscriber\Logs;

use App\Entity\Centre;
use App\Entity\Interface\LogActivitySubscriberInterface;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class RelaySubscriber implements EventSubscriberInterface, LogActivitySubscriberInterface
{
    use UserAwareTrait;

    private const RELAY_NAME = 'Relay';

    public function __construct(private readonly LoggerInterface $relayLogger, private Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::preUpdate, Events::preRemove];
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::RELAY_NAME));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->log($args, sprintf('%s updated :', self::RELAY_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s removed :', self::RELAY_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function log(LifecycleEventArgs $args, string $logType): void
    {
        $object = $args->getObject();

        if (!$object instanceof Centre) {
            return;
        }

        $this->relayLogger->info($logType, [
            'entity' => $object::class,
            'entity_id' => $object->getId(),
            'by_user_id' => $this->getUser()?->getId(),
        ]);
    }
}
