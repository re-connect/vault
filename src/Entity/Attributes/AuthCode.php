<?php

namespace App\Entity\Attributes;

use App\Entity\Client;
use App\Entity\User;
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

    public function getId()
    {
        return $this->id;
    }

    public function getClientId()
    {
        return $this->getClient()->getPublicId();
    }

    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getExpiresIn()
    {
        if ($this->expiresAt) {
            return $this->expiresAt - time();
        }

        return PHP_INT_MAX;
    }

    public function hasExpired()
    {
        if ($this->expiresAt) {
            return time() > $this->expiresAt;
        }

        return false;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getData()
    {
        return $this->getUser();
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }
}
