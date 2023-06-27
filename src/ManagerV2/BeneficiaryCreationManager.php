<?php

namespace App\ManagerV2;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\CreatorCentre;
use App\Entity\CreatorUser;
use App\ServiceV2\NotificationService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class BeneficiaryCreationManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly UserManager $userManager,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly RelayManager $relayManager,
        private readonly NotificationService $notificator,
    ) {
    }

    private function getOrCreateBeneficiary(BeneficiaryCreationProcess $creationProcess): Beneficiaire
    {
        $beneficiary = $creationProcess->getBeneficiary();
        if (!$beneficiary->getId()) {
            $user = $beneficiary->getUser();
            if (!$user->getCreatorUser()) {
                $user->addCreator((new CreatorUser())->setEntity($this->getUser()));
            }
            $user->setPassword($this->userManager->getRandomPassword());
            $this->em->persist($beneficiary);
            $this->em->persist($user);
        }

        return $beneficiary;
    }

    public function finishCreation(BeneficiaryCreationProcess $creationProcess): void
    {
        $creationProcess->setIsCreating(false);
        $this->em->flush();

        if ($creationProcess->isRemotely()) {
            $beneficiary = $creationProcess->getBeneficiary();
            $randomPassword = $this->userManager->getRandomPassword();
            $this->userManager->updatePassword($beneficiary->getUser(), $randomPassword);
            $this->notificator->sendFirstLoginSMS($beneficiary, $randomPassword);
        }
    }

    public function createOrUpdate(BeneficiaryCreationProcess $creationProcess): void
    {
        $beneficiary = $this->getOrCreateBeneficiary($creationProcess);
        $this->em->persist($creationProcess);
        $this->updatePassword($beneficiary);
        if ($creationProcess->isRelaysStep()) {
            $this->updateRelays($beneficiary);
        }
        $this->em->flush();
    }

    private function updatePassword(Beneficiaire $beneficiary): void
    {
        $this->userManager->updatePasswordWithPlain($beneficiary->getUser());
    }

    private function updateRelays(Beneficiaire $beneficiary): void
    {
        $user = $beneficiary->getUser();
        $beneficiaryRelays = $beneficiary->getCentres();
        $newRelays = $beneficiary->relays ?? new ArrayCollection();

        $newRelays
            ->filter(fn (Centre $relay) => !$beneficiaryRelays->contains($relay))
            ->map(fn (Centre $relay) => $this->relayManager->addUserToRelay($beneficiary->getUser(), $relay));

        $beneficiaryRelays
            ->filter(fn (Centre $relay) => !$newRelays->contains($relay))
            ->map(fn (Centre $relay) => $this->relayManager->removeUserFromRelay($beneficiary->getUser(), $relay));

        $hasRelays = count($newRelays) > 0;
        $creatorRelay = $user->getCreatorCentre();

        if ($hasRelays) {
            if (!$creatorRelay) {
                $user->addCreator((new CreatorCentre())->setEntity($newRelays->first()));
            } elseif ($creatorRelay->getEntity() !== $newRelays->first()) {
                $user->removeCreator($creatorRelay);
                $this->em->remove($creatorRelay);
                $user->addCreator((new CreatorCentre())->setEntity($newRelays->first()));
            }
        } else {
            if ($creatorRelay) {
                $user->removeCreator($creatorRelay);
                $this->em->remove($creatorRelay);
            }
        }

        $this->em->flush();
    }

    public function getOrCreate(?BeneficiaryCreationProcess $creationProcess, bool $remotely = false, int $step = 1): BeneficiaryCreationProcess
    {
        if (!$creationProcess) {
            $creationProcess = BeneficiaryCreationProcess::create($this->getUser(), $remotely);
            $creationProcess->setCurrentStep($step)->setLastReachedStep($step);
        } else {
            $creationProcess->setCurrentStep($step)->setLastReachedStep($step);
            $this->em->flush();
        }

        return $creationProcess;
    }
}
