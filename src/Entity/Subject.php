<?php

namespace App\Entity;

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

        if (null !== $this->getUser()) {
            $str = sprintf('%s', $this->getUser()->getUserIdentifier());
        }
        if (null !== $this->id) {
            $str .= sprintf(' (id:%s)', $this->getId());
        }

        return $str;
    }

    public function getFullName(): string
    {
        return $this->user->getFullName();
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
        return sprintf('%s.%s', $this->user->getSluggedLastname(), $this->user->getSluggedFirstName());
    }

    public function getUsername(): ?string
    {
        return $this->getUser()?->getUsername();
    }
}
