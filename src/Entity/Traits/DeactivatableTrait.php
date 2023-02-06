<?php

namespace App\Entity\Traits;

use App\Entity\User;

trait DeactivatableTrait
{
    protected bool $enabled = true;

    protected ?User $disabledBy = null;

    protected ?\DateTime $disabledAt = null;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getDisabledBy(): ?User
    {
        return $this->disabledBy;
    }

    public function setDisabledBy(?User $disabledBy): self
    {
        $this->disabledBy = $disabledBy;

        return $this;
    }

    public function getDisabledAt(): ?\DateTime
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTime $disabledAt): self
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }

    public function disable(?User $user): self
    {
        $this->setDisabledAt(new \DateTime())->setDisabledBy($user);

        return $this;
    }

    public function enable(): self
    {
        $this->setDisabledAt(null)->setDisabledBy(null);

        return $this;
    }
}
