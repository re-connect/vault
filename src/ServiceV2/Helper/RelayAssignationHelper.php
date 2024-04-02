<?php

namespace App\ServiceV2\Helper;

use App\Entity\User;
use App\Repository\CentreRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class RelayAssignationHelper
{
    public function __construct(private CentreRepository $repository, private EntityManagerInterface $em)
    {
    }

    public function assignRelaysFromIdsArray(User $user): void
    {
        foreach ($user->getRelaysIds() as $relayId) {
            $relay = $this->repository->find($relayId);
            $userRelay = User::createUserRelay($user, $relay);
            $this->em->persist($userRelay);
        }
    }
}
