<?php

namespace App\ListenerV2;

use App\Entity\Attributes\MembreCentre;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: MembreCentre::class)]
class ProDefaultPermissionListener
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function prePersist(MembreCentre $relayLink): void
    {
        if ($this->authorizationChecker->isGranted('MANAGE_BENEFICIARIES', $relayLink->getCentre())) {
            $relayLink->addPermission(MembreCentre::MANAGE_BENEFICIARIES_PERMISSION);
        }
    }
}
