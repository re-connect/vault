<?php

namespace App\Entity;

use Doctrine\Common\Collections\ReadableCollection;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class Subject implements \JsonSerializable
{
    #[Groups(['read', 'read-personal-data', 'read-personal-data-v2', 'v3:user:read'])]
    protected ?int $id = null;

    #[Groups(['read', 'v3:user:read', 'v3:beneficiary:write'])]
    protected ?User $user = null;

    public function __toString(): string
    {
        $str = '';

        if (null !== $this->user) {
            $str = sprintf('%s', $this->user->getUserIdentifier());
        }
        if (null !== $this->id) {
            $str .= sprintf(' (id:%s)', $this->id);
        }

        return $str;
    }

    public function getFullName(): string
    {
        return $this->user?->getFullName() ?? '';
    }

    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    abstract public function setUser(User $user = null);

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDefaultUsername(): string
    {
        return sprintf('%s.%s', $this->user?->getSluggedLastname() ?? '', $this->user?->getSluggedFirstName() ?? '');
    }

    public function getUsername(): ?string
    {
        return $this->user?->getUsername();
    }

    /** @return ReadableCollection<int, Centre> */
    public function getRelays(): ReadableCollection
    {
        return $this->user->getUserRelays();
    }

    public function hasRelays(): bool
    {
        return $this->getRelays()->count() > 0;
    }
}
