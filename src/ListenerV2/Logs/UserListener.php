<?php

namespace App\ListenerV2\Logs;

use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class UserListener implements LogActivityListenerInterface
{
    use UserAwareTrait;

    private const USER_NAME = 'User';

    public function __construct(private readonly LoggerInterface $userLogger, private readonly Security $security)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::USER_NAME));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $isLoginEventUpdate =
            2 === count($args->getEntityChangeSet())
            && $args->hasChangedField('updatedAt')
            && $args->hasChangedField('lastLogin');

        if (!$isLoginEventUpdate) {
            $this->log($args, sprintf('%s updated :', self::USER_NAME));
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
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
