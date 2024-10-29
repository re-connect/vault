<?php

namespace App\Entity\Attributes;

use App\Entity\Client;
use App\Entity\TokenInterface;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken implements TokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    protected ?Client $client = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    protected ?string $token = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $expiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    protected ?UserInterface $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?int
    {
        return $this->getClient()->getPublicId();
    }

    #[\Override]
    public function setExpiresAt($timestamp): static
    {
        $this->expiresAt = $timestamp;

        return $this;
    }

    #[\Override]
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    public function getExpiresIn(): int
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
    public function setToken($token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    #[\Override]
    public function setScope($scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    #[\Override]
    public function setUser(UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[\Override]
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getData(): UserInterface
    {
        return $this->getUser();
    }

    #[\Override]
    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
