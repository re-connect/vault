<?php

namespace App\Entity;

class BaseClient
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $randomId;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var array
     */
    protected $redirectUris = [];

    /**
     * @var array
     */
    protected $allowedGrantTypes = [];

    public function __construct()
    {
        $this->setRandomId($this->generateToken());
        $this->setSecret($this->generateToken());
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

    public function getSecret()
    {
        return $this->secret;
    }

    public function checkSecret($secret)
    {
        return null === $this->secret || $secret === $this->secret;
    }

    public function setRedirectUris(array $redirectUris)
    {
        $this->redirectUris = $redirectUris;
    }

    public function getRedirectUris()
    {
        return $this->redirectUris;
    }

    public function setAllowedGrantTypes(array $grantTypes): self
    {
        $this->allowedGrantTypes = $grantTypes;

        return $this;
    }

    public function getAllowedGrantTypes()
    {
        return $this->allowedGrantTypes;
    }

    public static function generateToken()
    {
        $bytes = random_bytes(32);

        return base_convert(bin2hex($bytes), 16, 36);
    }
}
