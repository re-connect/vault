<?php

namespace App\ManagerV2;

use App\Entity\Centre;
use App\Entity\User;
use App\Repository\CentreRepository;
use Doctrine\ORM\EntityManagerInterface;

class RelayManager
{
    private CentreRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(CentreRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    /**
     * @return Centre[]
     */
    public function findPersonalRelays(User $user, bool $isValid = true): array
    {
        return $this->repository->findPersonalRelays($user, $isValid);
    }

    public function acceptInvitation(User $user, Centre $relay): void
    {
        $userRelays = $user->isBeneficiaire()
            ? $user->getSubjectBeneficiaire()->getBeneficiairesCentres()
            : $user->getSubjectMembre()->getMembresCentres();

        foreach ($userRelays as $userRelay) {
            if ($userRelay->getCentre() === $relay) {
                $userRelay->setBValid(true);
                $this->em->flush();
            }
        }
    }

    public function leaveRelay(User $user, Centre $relay): void
    {
        $userRelays = $user->isBeneficiaire()
            ? $user->getSubjectBeneficiaire()->getBeneficiairesCentres()
            : $user->getSubjectMembre()->getMembresCentres();

        foreach ($userRelays as $userRelay) {
            if ($userRelay->getCentre() === $relay) {
                $this->em->remove($userRelay);
                $this->em->flush();
            }
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
