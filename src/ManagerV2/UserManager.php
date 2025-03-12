<?php

namespace App\ManagerV2;

use App\Entity\Attributes\Administrateur;
use App\Entity\Attributes\Association;
use App\Entity\Attributes\Beneficiaire;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Event\UserEvent;
use App\Repository\UserRepository;
use App\ServiceV2\Helper\PasswordHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        private readonly PasswordHelper $passwordHelper,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function isPasswordValid(User $user, ?string $password): bool
    {
        return $password && $this->hasher->isPasswordValid($user, $password);
    }

    public function getRandomPassword(int $length = User::USER_PASSWORD_LENGTH): string
    {
        try {
            $upperCount = random_int(1, 2);
            $numberCount = random_int(1, 2);
        } catch (\Exception) {
            $upperCount = 2;
            $numberCount = 2;
        }

        return str_shuffle(sprintf(
            '%s%s%s',
            ByteString::fromRandom($upperCount, implode('', range('A', 'Z')))->toString(),
            ByteString::fromRandom($numberCount, '0123456789')->toString(),
            ByteString::fromRandom($length - $upperCount - $numberCount, implode('', range('a', 'z')))->toString(),
        ));
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setHasPasswordWithLatestPolicy($this->passwordHelper->isStrongPassword($password));
        $this->em->flush();
        $user->eraseCredentials();
    }

    public function updatePasswordWithPlain(User $user): void
    {
        $this->em->persist($user);
        if ($password = $user->getPlainPassword()) {
            $this->updatePassword($user, $password);
        }
    }

    public function createRandomPassword(User $user): void
    {
        $this->em->persist($user);
        $this->updatePassword($user, $this->getRandomPassword(32));
    }

    public function setUniqueUsername(User $user): void
    {
        $user->setUsername($this->getUniqueUsername($user));
    }

    public function getUniqueUsername(User $user): string
    {
        $baseUsername = $user->isAdministrateur() || $user->isSuperAdmin() ? $user->getDefaultAdminUserName() : $user->getDefaultUsername();
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

    public function handleUserLogin(User $user): void
    {
        if (!$user->hasLoginToday()) {
            $user->setLastLogin(new \DateTime());
            $this->em->flush();
        }
        $this->dispatcher->dispatch(new UserEvent($user, !$user->hasLoginToday()));
    }
}
