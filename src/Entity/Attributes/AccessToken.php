<?php

namespace App\Entity\Attributes;

use App\Entity\Client;
use App\Entity\TokenInterface;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;

#[ORM\Table(name: 'accesstoken')]
#[ORM\Index(columns: ['user_id'], name: 'idx_f4cbb726a76ed395')]
#[ORM\Index(columns: ['client_id'], name: 'idx_f4cbb72619eb6921')]
#[ORM\UniqueConstraint(name: 'uniq_f4cbb7265f37a13b', columns: ['token'])]
#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken implements TokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Client::class)]
    protected Client $client;

    #[ORM\Column(name: 'token', type: 'string', length: 255, nullable: false)]
    protected string $token;

    #[ORM\Column(name: 'expiresAt', type: 'integer', nullable: true)]
    protected ?int $expiresAt = null;

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    protected ?User $user = null;

    public function getId()
    {
        return $this->id;
    }

    public function getClientId()
    {
        return $this->getClient()->getPublicId();
    }

    #[\Override]
    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
    }

    #[\Override]
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

    #[\Override]
    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    #[\Override]
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    #[\Override]
    public function setUser($user)
    {
        $this->user = $user;
    }

    #[\Override]
    public function getUser()
    {
        return $this->user;
    }

    public function getData()
    {
        return $this->getUser();
    }

    #[\Override]
    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }
}
