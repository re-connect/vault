<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;

class BaseClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'random_id', type: 'string', length: 255, nullable: false)]
    protected string $randomId;

    #[ORM\Column(name: 'secret', type: 'string', length: 255, nullable: false)]
    protected string $secret;

    #[ORM\Column(name: 'redirect_uris', type: 'array', nullable: false)]
    protected array $redirectUris = [];

    #[ORM\Column(name: 'allowed_grant_types', type: 'array', nullable: false)]
    protected array $allowedGrantTypes = [];

    public function __construct()
    {
        $this->setRandomId(static::generateToken());
        $this->setSecret(static::generateToken());
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRandomId($random): self
    {
        $this->randomId = $random;

        return $this;
    }

    public function getRandomId()
    {
        return $this->randomId;
    }

    public function getPublicId()
    {
        return sprintf('%s_%s', $this->getId(), $this->getRandomId());
    }

    public function setSecret($secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function checkSecret($secret): bool
    {
        return null === $this->secret || $secret === $this->secret;
    }

    public function setRedirectUris(array $redirectUris): void
    {
        $this->redirectUris = $redirectUris;
    }

    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    public function setAllowedGrantTypes(array $grantTypes): self
    {
        $this->allowedGrantTypes = $grantTypes;

        return $this;
    }

    public function getAllowedGrantTypes(): array
    {
        return $this->allowedGrantTypes;
    }

    public static function generateToken(): string
    {
        $bytes = random_bytes(32);

        return base_convert(bin2hex($bytes), 16, 36);
    }
}
