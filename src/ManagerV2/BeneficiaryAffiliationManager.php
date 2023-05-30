<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\User;
use App\FormV2\UserAffiliation\Model\SearchBeneficiaryFormModel;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class BeneficiaryAffiliationManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly BeneficiaireRepository $beneficiaryRepository,
        private readonly EntityManagerInterface $em,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RelayManager $relayManager,
        private Security $security,
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
        return strtolower($secretAnswer) === strtolower($beneficiary->getReponseSecrete());
    }

    /**
     * @return ArrayCollection<int, Centre>
     */
    public function getAvailableRelaysForAffiliation(User $professional, Beneficiaire $beneficiary): ArrayCollection
    {
        return $professional->getSubject()->getCentres()->filter(
            fn (Centre $relay) => !$beneficiary->getCentres()->contains($relay)
        );
    }

    /**
     * @param Collection<int, Centre> $relays
     */
    public function affiliateBeneficiary(Beneficiaire $beneficiary, Collection $relays, bool $accepted): void
    {
        $professional = $this->getUser();

        if (!$this->authorizationChecker->isGranted('ROLE_MEMBRE', $professional)) {
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
        $this->relayManager->removeUserFromRelay($beneficiary->getUser(), $relay);
    }
}
