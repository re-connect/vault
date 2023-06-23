<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ContactManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContactRepository $repository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return Contact[]
     */
    public function getContacts(Beneficiaire $beneficiary, string $search = null): array
    {
        return $this->repository->findByBeneficiary(
            $beneficiary,
            $this->isLoggedInUser($beneficiary->getUser()),
            $search,
        );
    }

    public function toggleVisibility(Contact $contact): void
    {
        $contact->toggleVisibility();
        $this->em->flush();
    }
}
