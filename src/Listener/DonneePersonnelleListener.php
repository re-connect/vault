<?php

namespace App\Listener;

use App\Entity\Document;
use App\Provider\DocumentProvider;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Document::class)]
class DonneePersonnelleListener
{
    public function __construct(private readonly DocumentProvider $provider)
    {
    }

    public function preUpdate(Document $document, PreUpdateEventArgs $args): void
    {
        if (array_key_exists('nom', $args->getEntityChangeSet()) && $args->getEntityChangeSet()['nom'][0] !== $args->getEntityChangeSet()['nom'][1]) {
            $entityChangeSet = $args->getEntityChangeSet();
            [$oldNom, $newNom] = $entityChangeSet['nom'];
            $document->setNom($oldNom);
            $this->provider->rename($document, $newNom, false);
        }
    }
}
