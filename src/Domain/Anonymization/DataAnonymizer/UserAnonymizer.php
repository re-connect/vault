<?php

namespace App\Domain\Anonymization\DataAnonymizer;

use App\Domain\Anonymization\FixtureGenerator;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserAnonymizer
{
    final public const string BASE_QUERY = 'UPDATE %s u SET %s WHERE u.id = %d';

    public function __construct(private EntityManagerInterface $em)
    {
    }

    private function createQuery(User $user, string $columnsUpdate): string
    {
        return sprintf(self::BASE_QUERY, User::class, $columnsUpdate, $user->getId());
    }

    /**
     * @param User[] $users
     */
    public function anonymizeUsers(array $users): void
    {
        foreach ($users as $user) {
            $firstname = FixtureGenerator::generateRandomFirstName();
            $lastname = FixtureGenerator::generateRandomLastName();
            $birthDate = $user->getBirthDate()?->format('d/m/Y');

            $this->anonymizeUserInformation($user, $firstname, $lastname, $birthDate);

            if ($user->getEmail()) {
                $this->anonymizeEmail($user, FixtureGenerator::generateRandomEmail($lastname, $firstname));
            }

            if ($user->getTelephone()) {
                $this->anonymizePhone($user, FixtureGenerator::generateRandomPhoneNumber());
            }
        }
    }

    private function anonymizeUserInformation(User $user, string $firstname, string $lastname, ?string $birthDate = null): void
    {
        $dql = $this->createQuery(
            $user,
            sprintf(
                "u.prenom = '%s', u.nom = '%s', u.username = '%s'",
                $firstname,
                $lastname,
                FixtureGenerator::generateUsername($user, $firstname, $lastname, $birthDate),
            ),
        );

        $this->em->createQuery($dql)->execute();
    }

    private function anonymizeEmail(User $user, string $newEmail): void
    {
        $dql = $this->createQuery(
            $user,
            sprintf("u.email = '%s'", $newEmail),
        );

        $this->em->createQuery($dql)->execute();
    }

    private function anonymizePhone(User $user, string $phone): void
    {
        $dql = $this->createQuery(
            $user,
            sprintf("u.telephone = '%s'", $phone),
        );

        $this->em->createQuery($dql)->execute();
    }
}
