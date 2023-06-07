<?php

namespace App\EventSubscriber\Logs;

use App\Entity\DonneePersonnelle;
use App\Entity\Interface\LogActivitySubscriberInterface;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PersonalDataSubscriber implements EventSubscriberInterface, LogActivitySubscriberInterface
{
    use UserAwareTrait;

    private const PERSONAL_DATA_NAME = 'Personal data';

    public function __construct(private readonly LoggerInterface $personalDataLogger, private Security $security)
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
        $this->log($args, sprintf('%s created :', self::PERSONAL_DATA_NAME));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->log($args, sprintf('%s updated :', self::PERSONAL_DATA_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->log($args, sprintf('%s removed :', self::PERSONAL_DATA_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function log(LifecycleEventArgs $args, string $logType): void
    {
        $object = $args->getObject();

        if (!$object instanceof DonneePersonnelle) {
            return;
        }

        if ($args instanceof PreUpdateEventArgs && $args->hasChangedField('bPrive')) {
            $logType = $object->getBprive()
                ? sprintf('%s switched private :', self::PERSONAL_DATA_NAME)
                : sprintf('%s switched public :', self::PERSONAL_DATA_NAME);
        }

        $this->personalDataLogger->info($logType, [
            'entity' => $object::class,
            'entity_id' => $object->getId(),
            'user_id' => $object->getBeneficiaire()?->getUser()?->getId(),
            'by_user_id' => $this->getUser()?->getId(),
        ]);
    }
}
