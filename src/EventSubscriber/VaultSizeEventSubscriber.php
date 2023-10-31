<?php

namespace App\EventSubscriber;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Document::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Document::class)]
class VaultSizeEventSubscriber
{
    public function prePersist(Document $document): void
    {
        $beneficiaire = $document->getBeneficiaire();
        $documentSize = $document->getTaille();
        if ($documentSize && $beneficiaire) {
            $beneficiaire->useVaultSpace($documentSize);
        }
    }

    public function preRemove(Document $document): void
    {
        $beneficiaire = $document->getBeneficiaire();
        $documentSize = $document->getTaille();
        if ($documentSize && $beneficiaire) {
            $beneficiaire->freeVaultSpace($documentSize);
        }
    }
}
