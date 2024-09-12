<?php

namespace App\ManagerV2;

use App\Entity\Attributes\Centre;
use App\Entity\Attributes\User;
use App\Entity\Attributes\UserCentre;
use App\Repository\CentreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\EntityManagerInterface;

readonly class RelayManager
{
    public function __construct(private EntityManagerInterface $em, private CentreRepository $relayRepository)
    {
    }

    public function acceptRelay(User $user, Centre $relay): void
    {
        if ($subjectRelay = $user->getUserRelay($relay)) {
            $subjectRelay->setBValid(true);
            $this->em->flush();
        }
    }

    public function leaveRelay(User $user, Centre $relay): void
    {
        if ($subjectRelay = $user->getUserRelay($relay)) {
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

    public function toggleUserInvitationToRelay(User $user, Centre $relay): void
    {
        if ($user->isLinkedToRelay($relay)) {
            $this->removeUserFromRelay($user, $relay);
        } else {
            $this->inviteUserToRelay($user, $relay);
        }
    }

    public function inviteUserToRelay(User $user, Centre $relay): void
    {
        $this->addNewRelays($user, new ArrayCollection([$relay]));

        $this->em->flush();
    }

    /** @return UserCentre[] */
    public function getPendingRelays(?User $user): array
    {
        return !$user || !($user->isBeneficiaire() || $user->isMembre())
            ? []
            : $this->relayRepository->findUserRelays($user, false);
    }

    /** @param ReadableCollection<int, Centre> $newRelays */
    public function addNewRelays(User $user, ReadableCollection $newRelays): void
    {
        $beneficiary = $user->getSubjectBeneficiaire();
        $forceAffiliation = $beneficiary && $beneficiary->getCreationProcess()?->getIsCreating();

        foreach ($newRelays as $relay) {
            if (!$user->isLinkedToRelay($relay)) {
                $this->em->persist(User::createUserRelay($user, $relay, $forceAffiliation));
            }
        }
    }
}
