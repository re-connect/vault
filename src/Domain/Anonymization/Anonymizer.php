<?php

namespace App\Domain\Anonymization;

use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class Anonymizer
{
    public function __construct(private EntityManagerInterface $em, private LoggerInterface $logger, private AnonymizationMailer $anonymizationMailer)
    {
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
            $this->anonymizeUsers($users);
            var_dump('Anonymize Documents');
            $this->anonymizeDocuments();
            var_dump('Anonymize Notes');
            $this->anonymizeNotes();
            var_dump('Anonymize Contacts');
            $this->anonymizeContacts();
            var_dump('Anonymize Events');
            $this->anonymizeEvents();
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

    /**
     * @param User[] $users
     */
    private function anonymizeUsers(array $users): void
    {
        foreach ($users as $user) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $lastname = FixtureGenerator::generateRandomLastName();
            $birthDate = $user->getBirthDate()?->format('d/m/Y');
            $email = FixtureGenerator::generateRandomEmail($lastname, $firstname);

            $dql = sprintf(
                "UPDATE %s u
                SET u.prenom = '%s', u.nom = '%s', u.email = '%s', u.telephone = '%s', u.username = '%s'
                WHERE u.id = %d",
                User::class,
                $firstname,
                $lastname,
                $email,
                FixtureGenerator::generateRandomPhoneNumber(),
                FixtureGenerator::generateUsername($user, $firstname, $lastname, $birthDate),
                $user->getId(),
            );
            $this->em->createQuery($dql)->execute();
        }
    }

    private function anonymizeContacts(): void
    {
        foreach (FixtureGenerator::RANDOM_LAST_NAMES as $index => $lastName) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $dql = sprintf(
                "UPDATE %s c
                SET c.nom = '%s', c.prenom = '%s', c.email = '%s', c.telephone = '%s', c.commentaire = '%s'
                WHERE MOD(c.id, 50) = %d",
                Contact::class,
                $lastName,
                $firstname,
                FixtureGenerator::generateRandomEmail($lastName, $firstname),
                FixtureGenerator::generateRandomPhoneNumber(),
                FixtureGenerator::ANONYMIZED_CONTENT,
                $index,
            );

            $this->em->createQuery($dql)->execute();
        }
    }

    private function anonymizeNotes(): void
    {
        $dql = sprintf(
            "UPDATE %s n SET n.nom = '%s', n.contenu = '%s'",
            Note::class,
            FixtureGenerator::ANONYMIZED_SUBJECT,
            FixtureGenerator::ANONYMIZED_CONTENT,
        );

        $this->em->createQuery($dql)->execute();
    }

    private function anonymizeEvents(): void
    {
        $dql = sprintf("UPDATE %s e SET e.nom = '%s', e.lieu = '%s', e.commentaire = '%s'",
            Evenement::class,
            FixtureGenerator::ANONYMIZED_SUBJECT,
            FixtureGenerator::generateRandomAddress(),
            FixtureGenerator::ANONYMIZED_CONTENT,
        );

        $this->em->createQuery($dql)->execute();
    }

    private function anonymizeDocuments(): void
    {
        $dql = sprintf("UPDATE %s d SET d.objectKey = '%s', d.thumbnailKey = '%s', d.nom = '%s', d.extension = '%s'",
            Document::class,
            'anonymous.png',
            'anonymous-thumbnail.png',
            'Document anonymisé',
            'png',
        );

        $this->em->createQuery($dql)->execute();
    }
}
