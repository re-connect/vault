<?php

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait DeactivatableTrait
{
    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false)]
    protected bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: \User::class)]
    #[ORM\JoinColumn(name: 'disabledBy_id', referencedColumnName: 'id')]
    protected ?User $disabledBy = null;

    #[ORM\Column(name: 'disabledAt', type: 'datetime', nullable: true)]
    protected ?\DateTime $disabledAt = null;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isEnabledToString(): string
    {
        return $this->enabled ? 'Oui' : 'Non';
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

    public function disable(?User $user = null): self
    {
        $this->setDisabledAt(new \DateTime())->setDisabledBy($user);
        $this->enabled = false;

        return $this;
    }

    public function enable(): self
    {
        $this->setDisabledAt(null)->setDisabledBy(null);
        $this->enabled = true;

        return $this;
    }
}
