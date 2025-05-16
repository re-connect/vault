<?php

namespace App\ListenerV2\Logs;

use App\Entity\Attributes\DonneePersonnelle;
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
class PersonalDataListener implements LogActivityListenerInterface
{
    use UserAwareTrait;

    private const string PERSONAL_DATA_NAME = 'Personal data';

    public function __construct(private readonly LoggerInterface $personalDataLogger, private readonly Security $security)
    {
    }

    #[\Override]
    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->log($args, sprintf('%s created :', self::PERSONAL_DATA_NAME));
    }

    #[\Override]
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->log($args, sprintf('%s updated :', self::PERSONAL_DATA_NAME));
    }

    #[\Override]
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->log($args, sprintf('%s removed :', self::PERSONAL_DATA_NAME));
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    #[\Override]
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
