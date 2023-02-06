<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
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
    public function findPersonalRelays(Beneficiaire $beneficiary, bool $isValid = true): array
    {
        return $this->repository->findPersonalRelays($beneficiary, $isValid);
    }

    public function acceptInvitation(Beneficiaire $beneficiary, Centre $relay): void
    {
        foreach ($beneficiary->getBeneficiairesCentres() as $beneficiaryRelay) {
            if ($beneficiaryRelay->getCentre() === $relay) {
                $beneficiaryRelay->setBValid(true);
                $this->em->flush();
            }
        }
    }

    public function leaveRelay(Beneficiaire $beneficiary, Centre $relay): void
    {
        foreach ($beneficiary->getBeneficiairesCentres() as $beneficiaryRelay) {
            if ($beneficiaryRelay->getCentre() === $relay) {
                $this->em->remove($beneficiaryRelay);
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
