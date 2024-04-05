<?php

namespace App\Traits;

use Symfony\Component\Serializer\Annotation\Groups;

trait GedmoTimedTrait
{
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    private ?\DateTime $createdAt;

    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    private ?\DateTime $updatedAt;

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
