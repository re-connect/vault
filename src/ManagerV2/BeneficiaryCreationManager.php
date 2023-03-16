<?php

namespace App\ManagerV2;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\CreatorCentre;
use App\Entity\CreatorUser;
use App\FormV2\UserCreation\CreateBeneficiaryType;
use App\ServiceV2\NotificationService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class BeneficiaryCreationManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly UserManager $userManager,
        private Security $security,
        private readonly EntityManagerInterface $em,
        private readonly RelayManager $relayManager,
        private readonly NotificationService $notificator,
    ) {
    }

    private function createBeneficiary(Beneficiaire $beneficiary): void
    {
        $user = $beneficiary->getUser();
        if (!$user->getCreatorUser()) {
            $user->addCreator((new CreatorUser())->setEntity($this->getUser()));
        }
        $user->setPassword($this->userManager->getRandomPassword());
        $this->em->persist($beneficiary);
        $this->em->persist($user);
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

    /**
     * @return string[]
     */
    public function getStepValidationGroup(bool $remotely, int $step): array
    {
        $validationGroup = $remotely ? CreateBeneficiaryType::REMOTELY_STEP_VALIDATION_GROUP : CreateBeneficiaryType::DEFAULT_STEP_VALIDATION_GROUP;

        return $validationGroup[$step] ?? [];
    }

    public function createOrUpdate(BeneficiaryCreationProcess $creationProcess): void
    {
        $beneficiary = $creationProcess->getBeneficiary();
        if (!$beneficiary->getId()) {
            $this->createBeneficiary($beneficiary);
        }
        $this->em->persist($creationProcess);
        $this->updatePassword($beneficiary);
        $this->updateRelays($beneficiary);
        $this->em->flush();
    }

    private function updatePassword(Beneficiaire $beneficiary): void
    {
        $user = $beneficiary->getUser();
        if ($password = $user->getPlainPassword()) {
            $this->userManager->updatePassword($user, $password);
        }
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
}
