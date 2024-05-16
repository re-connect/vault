<?php

namespace App\Domain\Anonymization;

readonly class AnonymizationCount
{
    public function __construct(
        private int $usersCount,
        private int $documentsCount,
        private int $contactsCount,
        private int $notesCount,
        private int $eventsCount,
    ) {
    }

    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    public function getDocumentsCount(): int
    {
        return $this->documentsCount;
    }

    public function getContactsCount(): int
    {
        return $this->contactsCount;
    }

    public function getNotesCount(): int
    {
        return $this->notesCount;
    }

    public function getEventsCount(): int
    {
        return $this->eventsCount;
    }
}
