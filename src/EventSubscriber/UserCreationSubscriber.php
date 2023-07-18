<?php

namespace App\EventSubscriber;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\User;
use App\ManagerV2\UserManager;
use App\ServiceV2\MailerService;
use App\ServiceV2\NotificationService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
class UserCreationSubscriber
{
    use UserAwareTrait;

    public function __construct(
        private readonly ApiClientManager $apiClientManager,
        private readonly NotificationService $notificationService,
        private readonly MailerService $mailerService,
        private readonly UserManager $manager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly Security $security,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $object = $event->getObject();
        if ($object instanceof User && $this->hasUsernameInformationChanged($event)) {
            $this->manager->setUniqueUsername($object);
        }
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Beneficiaire) {
            $user = $object->getUser();
            $this->initPassword($user);
            $this->addCreators($user);
            $this->setupClientLink($object);
        } elseif ($object instanceof User) {
            $user = $object;
        } else {
            return;
        }

        $this->manager->setUniqueUsername($user);
        $this->mailerService->sendDuplicateUsernameAlert($user);
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
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

        if ($client && Client::CLIENT_ROSALIE === $client->getNom() && $user?->getTelephone()) {
            $this->notificationService->sendVaultCreatedSms($user);
        }
    }

    private function addCreatorRelay(?User $user): void
    {
        if ($firstRelay = $user?->getFirstUserRelay()) {
            $user->addCreatorRelay($firstRelay->getCentre());
        }
    }

    private function initPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword() ?? $this->manager->getRandomPassword();
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
    }

    private function setupClientLink(Beneficiaire $beneficiary): void
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        $distantId = $beneficiary->distantId;
        if ($client && $distantId) {
            $beneficiary->addClientExternalLink($client, $distantId);
        }
    }

    private function addCreatorUser(User $user): void
    {
        if ($this->getUser() instanceof User) {
            $user->addCreatorUser($this->getUser());
        }
    }

    private function addCreatorClient(User $user): void
    {
        if ($client = $this->apiClientManager->getCurrentOldClient()) {
            $user->addCreatorClient($client);
        }
    }

    private function addCreators(?User $user): void
    {
        $this->addCreatorUser($user);
        $this->addCreatorRelay($user);
        $this->addCreatorClient($user);
    }

    private function hasUsernameInformationChanged(PreUpdateEventArgs $event): bool
    {
        return $event->hasChangedField('nom')
            || $event->hasChangedField('prenom')
            || $event->hasChangedField('birthDate');
    }
}
