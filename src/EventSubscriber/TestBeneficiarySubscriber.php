<?php

namespace App\EventSubscriber;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;

class TestBeneficiarySubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove];
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->update($args);
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->update($args);
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Centre || $object instanceof BeneficiaireCentre) {
            $this->update($args);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    private function update(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        $users = match (true) {
            $object instanceof Beneficiaire => [$object->getUser()],
            $object instanceof User && $object->isBeneficiaire() => [$object],
            $object instanceof Centre => $object->getBeneficiairesCentres()->map(fn (BeneficiaireCentre $beneficiaryRelay) => $beneficiaryRelay->getBeneficiaire()->getUser())->toArray(),
            $object instanceof BeneficiaireCentre => [$object->getBeneficiaire()->getUser()],
            default => [],
        };

        foreach ($users as $user) {
            $user->setTest($user->hasTestCreatorRelay() && $user->isAffiliatedToTestRelaysOnly());
            $args->getObjectManager()->flush();
        }
    }
}
