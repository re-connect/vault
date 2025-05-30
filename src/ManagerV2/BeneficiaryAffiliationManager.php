<?php

namespace App\ManagerV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\BeneficiaireCentre;
use App\Entity\Attributes\Centre;
use App\Entity\Attributes\User;
use App\FormV2\UserAffiliation\Model\SearchBeneficiaryFormModel;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BeneficiaryAffiliationManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly BeneficiaireRepository $beneficiaryRepository,
        private readonly EntityManagerInterface $em,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RelayManager $relayManager,
        private readonly Security $security,
    ) {
    }

    /**
     * @return Beneficiaire[]
     */
    public function searchByUsernameInformation(?string $firstname, ?string $lastname, ?\DateTime $birthDate): array
    {
        return $firstname || $lastname || $birthDate
            ? $this->beneficiaryRepository->searchByUsernameInformation($firstname, $lastname, $birthDate)
            : [];
    }

    /**
     * @return Beneficiaire[]
     */
    public function getBeneficiariesFromSearch(SearchBeneficiaryFormModel $formModel): array
    {
        return $this->searchByUsernameInformation(
            $formModel->getFirstname(),
            $formModel->getLastname(),
            $formModel->getBirthDate(),
        );
    }

    public function isSecretAnswerValid(Beneficiaire $beneficiary, ?string $secretAnswer): bool
    {
        return $secretAnswer && strtolower($secretAnswer) === strtolower((string) $beneficiary->getReponseSecrete());
    }

    /**
     * @return ArrayCollection<int, Centre>
     */
    public function getAvailableRelaysForAffiliation(User $professional, Beneficiaire $beneficiary): ArrayCollection
    {
        return $professional->getCentres()->filter(
            fn (Centre $relay) => !$beneficiary->getCentres()->contains($relay)
        );
    }

    /**
     * @param Collection<int, Centre> $relays
     */
    public function affiliateBeneficiary(Beneficiaire $beneficiary, Collection $relays, bool $accepted): void
    {
        $professional = $this->getUser();

        if (!$professional || !$this->authorizationChecker->isGranted('ROLE_MEMBRE', $professional)) {
            return;
        }

        foreach ($relays as $relay) {
            $beneficiaryRelay = (new BeneficiaireCentre())
                ->setUser($professional)
                ->setCentre($relay)
                ->setBeneficiaire($beneficiary)
                ->setBValid($accepted);

            $this->em->persist($beneficiaryRelay);
        }

        $this->em->flush();
    }

    public function disaffiliateBeneficiary(Beneficiaire $beneficiary, Centre $relay): void
    {
        $user = $beneficiary->getUser();
        if ($user) {
            $this->relayManager->removeUserFromRelay($user, $relay);
        }
    }

    public function forceAcceptInvitations(Beneficiaire $beneficiary): void
    {
        $user = $this->getUser();
        foreach ($beneficiary->getUserCentres() as $userCentre) {
            if ($user?->hasValidLinkToRelay($userCentre->getCentre())) {
                $userCentre->setBValid(true);
            }
        }
        $this->em->flush();
    }

    public function isSmsCodeValid(Beneficiaire $beneficiary, string $code): bool
    {
        return $beneficiary->getRelayInvitationSmsCode() === $code;
    }

    public function resetAffiliationSmsCode(Beneficiaire $beneficiary): void
    {
        $beneficiary->resetAffiliationSmsCode();
        $this->em->flush();
    }
}
