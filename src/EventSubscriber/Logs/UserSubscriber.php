<?php

namespace App\EventSubscriber\Logs;

use App\Entity\Interface\LogActivitySubscriberInterface;
use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserSubscriber implements EventSubscriberInterface, LogActivitySubscriberInterface
{
    use UserAwareTrait;

    private const USER_NAME = 'User';

    public function __construct(private readonly LoggerInterface $userLogger, private readonly Security $security)
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
        $this->log($args, sprintf('%s created :', self::USER_NAME));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $isLoginEventUpdate =
            2 === count($args->getEntityChangeSet())
            && $args->hasChangedField('updatedAt')
            && ($args->hasChangedField('derniereConnexionAt') || $args->hasChangedField('lastLogin'));

        if (!$isLoginEventUpdate) {
            $this->log($args, sprintf('%s updated :', self::USER_NAME));
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s removed :', self::USER_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function log(LifecycleEventArgs $args, string $logType): void
    {
        $object = $args->getObject();

        if (!$object instanceof User) {
            return;
        }

        $this->userLogger->info($logType, [
            'entity' => $object->getSubject() ? $object->getSubject()::class : $object::class,
            'user_id' => $object->getId(),
            'by_user_id' => $this->getUser()?->getId(),
        ]);
    }
}
