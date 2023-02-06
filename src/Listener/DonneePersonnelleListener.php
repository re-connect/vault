<?php

namespace App\Listener;

use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Provider\DocumentProvider;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DonneePersonnelleListener
{
    private DocumentProvider $provider;

    public function __construct(DocumentProvider $provider)
    {
        $this->provider = $provider;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (($entity instanceof Document) && array_key_exists('nom', $args->getEntityChangeSet()) && $args->getEntityChangeSet()['nom'][0] !== $args->getEntityChangeSet()['nom'][1]) {
            $entityChangeSet = $args->getEntityChangeSet();
            [$oldNom, $newNom] = $entityChangeSet['nom'];
            $entity->setNom($oldNom);
            $this->provider->rename($entity, $newNom, false);
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Contact && !$entity instanceof Evenement && !$entity instanceof Document && !$entity instanceof Note) {
            return;
        }

        if (null !== $entity->getId()) {
            return;
        }

        $this->provider->addCreatorCentre($entity);
        $this->provider->addCreatorUser($entity);
    }
}
