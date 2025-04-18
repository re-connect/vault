<?php

namespace App\ListenerV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: DonneePersonnelle::class)]
class CreatorListener
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security, private readonly ApiClientManager $apiClientManager)
    {
    }

    public function prePersist(DonneePersonnelle $personalData): void
    {
        $user = $this->getUser();
        $client = $this->apiClientManager->getCurrentOldClient();

        if ($user) {
            $personalData->addCreator((new CreatorUser())->setEntity($user));
        }

        if ($client) {
            $personalData->addCreator((new CreatorClient())->setEntity($client));
        }
    }
}
