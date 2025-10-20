<?php

namespace App\ListenerV2\Logs;

use App\Entity\Centre;
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
class RelayListener implements LogActivityListenerInterface
{
    use UserAwareTrait;

    private const string RELAY_NAME = 'Relay';

    public function __construct(private readonly LoggerInterface $relayLogger, private readonly Security $security)
    {
    }

    #[\Override]
    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::RELAY_NAME));
    }

    #[\Override]
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->log($args, sprintf('%s updated :', self::RELAY_NAME));
    }

    #[\Override]
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->log($args, sprintf('%s removed :', self::RELAY_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    #[\Override]
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
