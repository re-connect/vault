<?php

namespace App\EventSubscriber;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\User;
use App\ManagerV2\UserManager;
use App\ServiceV2\MailerService;
use App\ServiceV2\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ApiClientManager $apiClientManager,
        private readonly NotificationService $notificationService,
        private readonly MailerService $mailerService,
        private readonly UserManager $manager,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate, Events::postPersist];
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $object = $event->getObject();
        if ($object instanceof User) {
            $this->manager->setUniqueUsername($object);
        }
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Beneficiaire) {
            $user = $object->getUser();
            $this->initPassword($user);
            $this->setupLinks($object);
        } elseif ($object instanceof User) {
            $user = $object;
        } else {
            return;
        }

        $this->manager->setUniqueUsername($user);
        $this->mailerService->sendDuplicateUsernameAlert($user);
    }

    private function initPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword() ?? $this->manager->getRandomPassword();
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
    }

    private function setupLinks(Beneficiaire $beneficiary): void
    {
        if (!$client = $this->apiClientManager->getCurrentOldClient()) {
            return;
        }
        $beneficiary->addCreatorClient($client);

        if ($distantId = $beneficiary->distantId) {
            $beneficiary->addClientExternalLink($client, $distantId);
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if ($object instanceof Beneficiaire) {
            $this->sendPostCreationSms($object);
        }
    }

    public function sendPostCreationSms(Beneficiaire $beneficiary): void
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        $user = $beneficiary->getUser();

        if ($client && Client::CLIENT_ROSALIE_NEW === $client->getNom() && $user?->getTelephone()) {
            $this->notificationService->sendVaultCreatedSms($user);
        }
    }
}
