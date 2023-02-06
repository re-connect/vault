<?php

namespace App\Manager;

use App\Entity\Association;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserManager
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $repository
    ) {
    }

    private function sanitize($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil| caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = preg_replace('#[^.a-zA-Z]#', '', $str); // supprime les autres caractères
        $str = strtolower($str);

        return $str;
    }

    public function setUniqueGestionnaireUsername(User $user, bool $isTest): void
    {
        $username = sprintf($isTest
            ? '%s.%s-test'
            : '%s.%s',
            $this->sanitize($user->getNom()),
            $this->sanitize($user->getPrenom()),
        );

        $i = 1;
        $baseUsername = $username;

        while (null !== $this->repository->findByUsername($username)) {
            $username = $baseUsername.'.'.$i;
            ++$i;
        }

        $user->setUsername($username);
    }

    public function setUniqueAssociationUsername(Association $association, bool $isTest): void
    {
        $username = sprintf($isTest
            ? '%s-test'
            : '%s',
            $this->sanitize($association->getNom()),
        );

        $i = 1;
        $baseUsername = $username;

        while (null !== $this->repository->findByUsername($username)) {
            $username = $baseUsername.'.'.$i;
            ++$i;
        }

        $association->getUser()->setUsername($username);
    }

    public function authenticateUser($user)
    {
        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        $token = new UsernamePasswordToken($user, 'your_firewall_name', $user->getRoles());
        $this->tokenStorage->setToken($token); // now the user is logged in

        // now dispatch the login event
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $event = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($event, 'security.interactive_login');
        }
    }

    public function randomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        return substr(str_shuffle($chars), 0, $length);
    }

    public function compareSecretStrings($string1, $string2): bool
    {
        $string1 = strtolower($string1);
        $string1 = preg_replace('#[ _\-.]#', '', $string1);
        $string1 = $this->sanitize($string1);

        $string2 = strtolower($string2);
        $string2 = preg_replace('#[ _\-.]#', '', $string2);
        $string2 = $this->sanitize($string2);

        return hash_equals($string1, $string2);
    }

    public function testPassword(User $user, $password)
    {
        return $this->hasher->isPasswordValid($user, $password);
    }

    public function deleteUser($user)
    {
        /** @var User $user */
        if ($user->isBeneficiaire()) {
            foreach ($user->getSubjectBeneficiaire()->getDocuments() as $document) {
                $this->entityManager->remove($document);
            }
            foreach ($user->getSubjectBeneficiaire()->getDossiers() as $dosser) {
                $this->entityManager->remove($dosser);
            }
            foreach ($user->getSubjectBeneficiaire()->getEvenements() as $evenement) {
                $this->entityManager->remove($evenement);
            }
            foreach ($user->getSubjectBeneficiaire()->getNotes() as $note) {
                $this->entityManager->remove($note);
            }
            foreach ($user->getSubjectBeneficiaire()->getBeneficiairesCentres() as $beneficiaireCentre) {
                $this->entityManager->remove($beneficiaireCentre);
            }
            foreach ($user->getSubjectBeneficiaire()->getContacts() as $contact) {
                $this->entityManager->remove($contact);
            }
            $this->entityManager->remove($user->getSubjectBeneficiaire());
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function updatePassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if ($plainPassword) {
            $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        }

        $user->eraseCredentials();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->setPlainPassword($newPassword);
        $this->updatePassword($user);
        $this->entityManager->flush();
    }

    public function updateCanonicalFields(User $user)
    {
        $user->setEmailCanonical($user->getEmail());
    }

    public function updateUser(User $user, $andFlush = true)
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);

        $this->entityManager->persist($user);
        if ($andFlush) {
            $this->entityManager->flush();
        }
    }
}
