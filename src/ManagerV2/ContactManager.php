<?php

namespace App\ManagerV2;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;

class ContactManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function toggleVisibility(Contact $contact): void
    {
        $contact->toggleVisibility();
        $this->em->flush();
    }
}
