<?php

namespace App\Domain\Anonymization\DataAnonymizer;

use App\Domain\Anonymization\FixtureGenerator;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;

readonly class PersonalDataAnonymizer
{
    final public const string BASE_QUERY = 'UPDATE %s d SET %s %s';

    public function __construct(private EntityManagerInterface $em)
    {
    }

    private function createQuery(string $className, string $columnsUpdate, ?string $whereStatement = null): string
    {
        return sprintf(self::BASE_QUERY, $className, $columnsUpdate, $whereStatement);
    }

    public function anonymizeContacts(): void
    {
        $className = Contact::class;
        $nullableColumns = [];

        foreach (FixtureGenerator::RANDOM_LAST_NAMES as $index => $lastName) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $nullableColumns['email'] = FixtureGenerator::generateRandomEmail($lastName, $firstname);
            $nullableColumns['telephone'] = FixtureGenerator::generateRandomEmail($lastName, $firstname);
            $nullableColumns['commentaire'] = FixtureGenerator::ANONYMIZED_CONTENT;

            $dql = $this->createQuery(
                $className,
                sprintf("d.nom = '%s', d.prenom = '%s'", $lastName, $firstname),
                sprintf('WHERE MOD(d.id, 50) = %d', $index),
            );
            $this->em->createQuery($dql)->execute();

            foreach ($nullableColumns as $nullableColumn => $value) {
                $dql = $this->createQuery(
                    $className,
                    sprintf("d.%s = '%s'", $nullableColumn, $value),
                    sprintf('WHERE MOD(d.id, 50) = %d AND d.%s IS NOT NULL', $index, $nullableColumn),
                );
                $this->em->createQuery($dql)->execute();
            }
        }
    }

    public function anonymizeNotes(): void
    {
        $dql = sprintf(
            "UPDATE %s n SET n.nom = '%s', n.contenu = '%s'",
            Note::class,
            FixtureGenerator::ANONYMIZED_SUBJECT,
            FixtureGenerator::ANONYMIZED_CONTENT,
        );

        $this->em->createQuery($dql)->execute();
    }

    public function anonymizeEvents(): void
    {
        $className = Evenement::class;
        $nullableColumns = [
            'commentaire' => FixtureGenerator::ANONYMIZED_CONTENT,
            'lieu' => null,
        ];

        foreach (FixtureGenerator::RANDOM_LAST_NAMES as $index => $lastName) {
            $nullableColumns['lieu'] = FixtureGenerator::generateRandomAddress();

            $dql = $this->createQuery(
                $className,
                sprintf("d.nom = '%s'", FixtureGenerator::ANONYMIZED_SUBJECT),
                sprintf('WHERE MOD(d.id, 50) = %d', $index),
            );
            $this->em->createQuery($dql)->execute();

            foreach ($nullableColumns as $nullableColumn => $value) {
                $dql = $this->createQuery(
                    $className,
                    sprintf("d.%s = '%s'", $nullableColumn, $value),
                    sprintf('WHERE MOD(d.id, 50) = %d AND d.%s IS NOT NULL', $index, $nullableColumn),
                );
                $this->em->createQuery($dql)->execute();
            }
        }
    }

    public function anonymizeDocuments(): void
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
