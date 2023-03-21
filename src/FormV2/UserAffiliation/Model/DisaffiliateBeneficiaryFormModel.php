<?php

namespace App\FormV2\UserAffiliation\Model;

use App\Entity\Centre;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class DisaffiliateBeneficiaryFormModel
{
    /**
     * @var Collection<int, Centre>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'beneficiary_disaffiliation_empty_relays'
    )]
    private Collection $relays;

    public function __construct()
    {
        $this->relays = new ArrayCollection();
    }

    /**
     * @return Collection<int, Centre>
     */
    public function getRelays(): Collection
    {
        return $this->relays;
    }

    /**
     * @param Collection<int, Centre> $relays
     */
    public function setRelays(Collection $relays): self
    {
        $this->relays = $relays;

        return $this;
    }
}
