<?php

namespace App\ListenerV2\Logs;

use App\Entity\MembreCentre;
use App\Entity\UserCentre;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preRemove)]
class AffiliationListener implements LogActivityListenerInterface
{
    use UserAwareTrait;

    private const AFFILIATION_NAME = 'Affiliation link';

    public function __construct(private readonly LoggerInterface $affiliationLogger, private readonly Security $security)
    {
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::AFFILIATION_NAME));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // This method is never called
        return;
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s deleted :', self::AFFILIATION_NAME));
    }

    public function log(LifecycleEventArgs $args, string $logType): void
    {
        $object = $args->getObject();

        if (!$object instanceof UserCentre) {
            return;
        }
        $user = $object instanceof MembreCentre ? $object->getMembre()?->getUser() : $object->getBeneficiaire()?->getUser();

        $this->affiliationLogger->info($logType, [
            'entity' => $object::class,
            'entity_id' => $object->getId(),
            'relay' => $object->getCentre()?->getId(),
            'user' => $user?->getId(),
            'by_user_id' => $this->getUser()?->getId(),
        ]);
    }
}
