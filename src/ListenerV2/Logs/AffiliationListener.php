<?php

namespace App\ListenerV2\Logs;

use App\Entity\UserCentre;
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
class AffiliationListener implements LogActivityListenerInterface
{
    use UserAwareTrait;

    private const string AFFILIATION_NAME = 'Affiliation link';

    public function __construct(private readonly LoggerInterface $affiliationLogger, private readonly Security $security)
    {
    }

    #[\Override]
    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::AFFILIATION_NAME));
    }

    #[\Override]
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField('bValid')) {
            return;
        }

        $userCentre = $args->getObject();
        if ($userCentre instanceof UserCentre && $userCentre->getBValid()) {
            $this->log($args, sprintf('%s accepted :', self::AFFILIATION_NAME));
        }
    }

    #[\Override]
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->log($args, sprintf('%s deleted :', self::AFFILIATION_NAME));
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    #[\Override]
    public function log(LifecycleEventArgs $args, string $logType): void
    {
        $userCentre = $args->getObject();

        if (!$userCentre instanceof UserCentre) {
            return;
        }

        $this->affiliationLogger->info($logType, [
            'entity' => $userCentre::class,
            'entity_id' => $userCentre->getId(),
            'relay' => $userCentre->getCentre()->getId(),
            'user' => $userCentre->getUser()?->getId(),
            'accepted' => $userCentre->getBValid(),
            'by_user_id' => $this->getUser()?->getId(),
        ]);
    }
}
