<?php

namespace App\ManagerV2;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;

class NoteManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function toggleVisibility(Note $note): void
    {
        $note->setBPrive(!$note->getBPrive());
        $this->em->flush();
    }
}
