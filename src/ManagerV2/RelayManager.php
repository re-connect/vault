<?php

namespace App\ManagerV2;

use App\Entity\Centre;
use App\Entity\User;
use App\Entity\UserCentre;
use App\Repository\CentreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\EntityManagerInterface;

class RelayManager
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CentreRepository $relayRepository)
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

    /**
     * @param ReadableCollection<int, Centre> $newRelays
     * @param ReadableCollection<int, Centre> $loggedInUserRelays
     */
    public function updateUserRelays(User $user, ReadableCollection $newRelays, ReadableCollection $loggedInUserRelays): void
    {
        $this->addNewRelays($user, $newRelays);
        $this->removeOutdatedRelays($user, $newRelays, $loggedInUserRelays);

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

    /**
     * @param ReadableCollection<int, Centre> $newRelays
     * @param ReadableCollection<int, Centre> $loggedInUserRelays
     */
    public function removeOutdatedRelays(User $user, ReadableCollection $newRelays, ReadableCollection $loggedInUserRelays): void
    {
        foreach ($user->getUserRelays() as $userRelay) {
            if ($loggedInUserRelays->contains($userRelay->getCentre()) && !$newRelays->contains($userRelay->getCentre())) {
                $this->em->remove($userRelay);
            }
        }
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
