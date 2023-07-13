<?php

namespace App\Entity;

class RefreshToken implements TokenInterface
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
}
