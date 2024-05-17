<?php

namespace App\Domain\Anonymization;

use App\Domain\Anonymization\DataAnonymizer\PersonalDataAnonymizer;
use App\Domain\Anonymization\DataAnonymizer\UserAnonymizer;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class Anonymizer
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private AnonymizationMailer $anonymizationMailer,
        private UserAnonymizer $userAnonymizer,
        private PersonalDataAnonymizer $personalDataAnonymizer,
    ) {
    }

    public function anonymizeDatabase(bool $sendEmail = true): void
    {
        var_dump('Counting data');
        $users = $this->em->getRepository(User::class)->findAnonymizables();
        $documentsCount = $this->em->getRepository(Document::class)->count([]);
        $contactsCount = $this->em->getRepository(Contact::class)->count([]);
        $notesCount = $this->em->getRepository(Note::class)->count([]);
        $eventsCount = $this->em->getRepository(Evenement::class)->count([]);

        $anonymizationCount = new AnonymizationCount(
            count($users),
            $documentsCount,
            $contactsCount,
            $notesCount,
            $eventsCount,
        );

        try {
            var_dump('Anonymize Users');
            $this->userAnonymizer->anonymizeUsers($users);
            var_dump('Anonymize Documents');
            $this->personalDataAnonymizer->anonymizeDocuments();
            var_dump('Anonymize Notes');
            $this->personalDataAnonymizer->anonymizeNotes();
            var_dump('Anonymize Contacts');
            $this->personalDataAnonymizer->anonymizeContacts();
            var_dump('Anonymize Events');
            $this->personalDataAnonymizer->anonymizeEvents();
            var_dump('Anonymization ended');

            if ($sendEmail) {
                var_dump('Sending email');
                $this->anonymizationMailer->notifyAnonymizationSuccess($anonymizationCount);
                var_dump('Anonymization email sent');
            }
        } catch (\Exception $e) {
            var_dump(sprintf('Error anonymizing database. cause: %s', $e->getMessage()));
            $this->logger->error(sprintf('Error anonymizing database. cause: %s', $e->getMessage()));
        }
    }
}
