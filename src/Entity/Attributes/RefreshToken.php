<?php

namespace App\Entity\Attributes;

use App\Entity\TokenInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'refreshtoken')]
#[ORM\Index(name: 'IDX_A8B2C36219EB6921', columns: ['client_id'])]
#[ORM\Index(name: 'IDX_A8B2C362A76ED395', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_A8B2C3625F37A13B', columns: ['token'])]
class RefreshToken implements TokenInterface
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    protected Client $client;

    #[ORM\Column(name: 'token', type: 'string', length: 255, nullable: false)]
    protected string $token;

    #[ORM\Column(name: 'expiresAt', type: 'integer', nullable: true)]
    protected ?int $expiresAt = null;

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    protected ?UserInterface $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId()
    {
        return $this->getClient()->getPublicId();
    }

    #[\Override]
    public function setExpiresAt($timestamp): void
    {
        $this->expiresAt = $timestamp;
    }

    #[\Override]
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    public function getExpiresIn(): ?int
    {
        if ($this->expiresAt) {
            return $this->expiresAt - time();
        }

        return PHP_INT_MAX;
    }

    public function hasExpired(): bool
    {
        if ($this->expiresAt) {
            return time() > $this->expiresAt;
        }

        return false;
    }

    #[\Override]
    public function setToken($token): void
    {
        $this->token = $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    #[\Override]
    public function setScope($scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    #[\Override]
    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    #[\Override]
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getData(): ?UserInterface
    {
        return $this->getUser();
    }

    #[\Override]
    public function setClient($client): void
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
