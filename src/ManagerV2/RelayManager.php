<?php

namespace App\ManagerV2;

use App\Entity\Centre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RelayManager
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function acceptInvitation(User $user, Centre $relay): void
    {
        if ($subjectRelay = $user->getSubjectRelaysForRelay($relay)) {
            $subjectRelay->setBValid(true);
            $this->em->flush();
        }
    }

    public function leaveRelay(User $user, Centre $relay): void
    {
        if ($subjectRelay = $user->getSubjectRelaysForRelay($relay)) {
            $this->em->remove($subjectRelay);
            $this->em->flush();
        }
    }

    public function removeUserFromRelay(User $user, Centre $relay): void
    {
        if ($userRelay = $user->getUserRelay($relay)) {
            $this->em->remove($userRelay);
        }

        $this->em->flush();
    }

    public function addUserToRelay(User $user, Centre $relay): void
    {
        $this->em->persist(User::createUserRelay($user, $relay));
        $this->em->flush();
    }
}
