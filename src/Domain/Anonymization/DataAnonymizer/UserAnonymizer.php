<?php

namespace App\Domain\Anonymization\DataAnonymizer;

use App\Domain\Anonymization\FixtureGenerator;
use App\Entity\User;

readonly class UserAnonymizer extends AbstractDataAnonymizer
{
    final public const string CLASS_NAME = User::class;

    /**
     * @param User[] $users
     *
     * @throws \Exception
     */
    public function anonymizeUsers(array $users): void
    {
        foreach ($users as $user) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $lastname = FixtureGenerator::generateRandomLastName();
            $birthDate = $user->getBirthDate()?->format('d/m/Y');

            $this->anonymizeUserInformation($user, $firstname, $lastname, $birthDate);
            $this->anonymizeEmail($user, FixtureGenerator::generateRandomEmail($lastname, $firstname));
            $this->anonymizePhone($user, FixtureGenerator::generateRandomPhoneNumber());
        }
    }

    private function anonymizeUserInformation(User $user, string $firstname, string $lastname, ?string $birthDate = null): void
    {
        $dql = $this->createQuery(
            self::CLASS_NAME,
            sprintf(
                "d.prenom = '%s', d.nom = '%s', d.username = '%s'",
                $firstname,
                $lastname,
                FixtureGenerator::generateUsername($user, $firstname, $lastname, $birthDate),
            ),
            sprintf('WHERE d.id = %d', $user->getId()),
        );

        $this->executeQuery($dql);
    }

    private function anonymizeEmail(User $user, string $newEmail): void
    {
        $dql = $this->createQuery(
            self::CLASS_NAME,
            sprintf("d.email = '%s'", $newEmail),
            sprintf('WHERE d.id = %d AND d.email IS NOT NULL', $user->getId()),
        );

        $this->executeQuery($dql);
    }

    private function anonymizePhone(User $user, string $phone): void
    {
        $dql = $this->createQuery(
            self::CLASS_NAME,
            sprintf("d.telephone = '%s'", $phone),
            sprintf('WHERE d.id = %d AND d.telephone IS NOT NULL', $user->getId()),
        );

        $this->executeQuery($dql);
    }
}
