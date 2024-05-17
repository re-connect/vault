<?php

namespace App\Domain\Anonymization\DataAnonymizer;

use App\Domain\Anonymization\FixtureGenerator;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;

readonly class PersonalDataAnonymizer extends AbstractDataAnonymizer
{
    /**
     * @throws \Exception
     */
    public function anonymizeContacts(): void
    {
        $className = Contact::class;
        $nullableColumns = [];

        foreach (FixtureGenerator::RANDOM_LAST_NAMES as $index => $lastName) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $nullableColumns['email'] = FixtureGenerator::generateRandomEmail($lastName, $firstname);
            $nullableColumns['telephone'] = FixtureGenerator::generateRandomPhoneNumber();
            $nullableColumns['commentaire'] = FixtureGenerator::ANONYMIZED_CONTENT;

            $dql = $this->createQuery(
                $className,
                sprintf("d.nom = '%s', d.prenom = '%s'", $lastName, $firstname),
                sprintf('WHERE MOD(d.id, 50) = %d', $index),
            );
            $this->executeQuery($dql);

            $this->anonymizeNullableColumns($className, $nullableColumns, $index);
        }
    }

    public function anonymizeNotes(): void
    {
        $dql = $this->createQuery(
            Note::class,
            sprintf("d.nom = '%s', d.contenu = '%s'",
                FixtureGenerator::ANONYMIZED_SUBJECT,
                FixtureGenerator::ANONYMIZED_CONTENT,
            ),
        );

        $this->executeQuery($dql);
    }

    /**
     * @throws \Exception
     */
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
            $this->executeQuery($dql);

            $this->anonymizeNullableColumns($className, $nullableColumns, $index);
        }
    }

    public function anonymizeDocuments(): void
    {
        $dql = $this->createQuery(
            Document::class,
            sprintf("d.objectKey = '%s', d.thumbnailKey = '%s', d.nom = '%s', d.extension = '%s'",
                'anonymous.png',
                'anonymous-thumbnail.png',
                'Document anonymisé',
                'png',
            ),
        );

        $this->executeQuery($dql);
    }

    /**
     * @param array<string, string> $columns
     */
    private function anonymizeNullableColumns(string $className, array $columns, int $index): void
    {
        foreach ($columns as $column => $value) {
            $dql = $this->createQuery(
                $className,
                sprintf("d.%s = '%s'", $column, $value),
                sprintf('WHERE MOD(d.id, 50) = %d AND d.%s IS NOT NULL', $index, $column),
            );
            $this->executeQuery($dql);
        }
    }
}
