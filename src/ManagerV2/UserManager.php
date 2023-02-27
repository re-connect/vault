<?php

namespace App\ManagerV2;

use App\Entity\Administrateur;
use App\Entity\Association;
use App\Entity\Beneficiaire;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\ByteString;

class UserManager
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $repository,
    ) {
    }

    public function isPasswordValid(User $user, ?string $password): bool
    {
        return $password && $this->hasher->isPasswordValid($user, $password);
    }

    public function getRandomPassword(int $length = 8): string
    {
        return ByteString::fromRandom($length)->toString();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->flush();
        $user->eraseCredentials();
    }

    public function setUniqueUsername(User $user): void
    {
        $user->setUsername($this->getUniqueUsername($user));
    }

    public function getUniqueUsername(User $user): string
    {
        $baseUsername = $user->getDefaultUsername();
        $homonyms = $this->repository->findHomonyms($baseUsername);
        $hasNoHomonym = 0 === count($homonyms);
        if ($hasNoHomonym) {
            return $baseUsername;
        }

        $isUserAmongHomonyms = (new ArrayCollection($homonyms))
            ->exists(fn (int $key, User $homonym) => $homonym->getId() === $user->getId());

        return $isUserAmongHomonyms ? $user->getUsername() : $this->getNextHomonymUsername($baseUsername, $homonyms);
    }

    public function remove(User $user): void
    {
        $this->removeSubject($user->getSubject());
        $this->em->remove($user);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Error deleting user (id = %d) : %s',
                    $user->getId(),
                    $e->getMessage(),
                )
            );
        }
    }

    private function removeSubject(Administrateur|Association|Beneficiaire|Gestionnaire|Membre|null $subject): void
    {
        if ($subject) {
            $this->em->remove($subject);
        }
    }

    private function getHomonymIndex(string $baseUsername, string $username): int
    {
        return (int) str_replace('-', '', str_replace($baseUsername, '', $username));
    }

    /**
     * @param User[] $homonyms
     */
    private function getMaxHomonymIndex(string $baseUsername, array $homonyms): int
    {
        return max(
            array_map(fn (User $homonym) => $this->getHomonymIndex($baseUsername, $homonym->getUsername()), $homonyms)
        );
    }

    /**
     * @param User[] $homonyms
     */
    private function getNextHomonymUsername(string $baseUsername, array $homonyms): string
    {
        return sprintf('%s-%d', $baseUsername, $this->getMaxHomonymIndex($baseUsername, $homonyms) + 1);
    }
}
