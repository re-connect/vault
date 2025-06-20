<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;
use League\Bundle\OAuth2ServerBundle\Repository\AuthCodeRepository;

#[ORM\Table(name: 'authcode')]
#[ORM\Index(columns: ['client_id'], name: 'IDX_A8931C1F19EB6921')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_A8931C1FA76ED395')]
#[ORM\UniqueConstraint(name: 'UNIQ_A8931C1F5F37A13B', columns: ['token'])]
#[ORM\Entity(repositoryClass: AuthCodeRepository::class)]
class AuthCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(name: 'token', type: 'string', length: 255, nullable: false)]
    protected string $token;

    #[ORM\Column(name: 'expiresAt', type: 'integer', nullable: true)]
    protected ?int $expiresAt = null;

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(name: 'redirectUri', type: 'text', length: 0, nullable: true)]
    protected ?string $redirectUri = null;

    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Client::class)]
    protected Client $client;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->getClient()->getPublicId();
    }

    public function setExpiresAt(?int $timestamp): void
    {
        $this->expiresAt = $timestamp;
    }

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

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getData(): ?User
    {
        return $this->getUser();
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setRedirectUri(?string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }
}
