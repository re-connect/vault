<?php

namespace App\ListenerV2;

use App\Entity\Attributes\Note;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Note::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Note::class)]
readonly class SanitizeNoteContentListener
{
    public function __construct(private HtmlSanitizerInterface $appNoteSanitizer)
    {
    }

    public function preUpdate(Note $note, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('contenu')) {
            $this->sanitize($note);
        }
    }

    public function prePersist(Note $note, PrePersistEventArgs $args): void
    {
        $this->sanitize($note);
    }

    private function sanitize(Note $note): void
    {
        $note->setContenu($this->appNoteSanitizer->sanitize($note->getContenu()));
    }
}
