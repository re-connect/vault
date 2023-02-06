<?php

namespace App\FormV2\UserAffiliation\Model;

use App\Entity\Centre;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class AffiliateBeneficiaryFormModel
{
    private ?string $secretAnswer = '';
    /**
     * @var Collection<int, Centre>
     */
    private Collection $relays;

    public function __construct()
    {
        $this->relays = new ArrayCollection();
    }

    public function getSecretAnswer(): ?string
    {
        return $this->secretAnswer;
    }

    public function setSecretAnswer(?string $secretAnswer): self
    {
        $this->secretAnswer = $secretAnswer;

        return $this;
    }

    public function getRelays(): Collection
    {
        return $this->relays;
    }

    public function setRelays(Collection $relays): self
    {
        $this->relays = $relays;

        return $this;
    }
}
