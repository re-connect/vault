<?php

namespace App\Entity;

class AccessToken implements TokenInterface
{
    /**
     * @var int
     */
    protected $id;

    protected $client;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $expiresAt;

    /**
     * @var string
     */
    protected $scope;

    protected $user;

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
