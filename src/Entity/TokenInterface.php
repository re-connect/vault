<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

interface TokenInterface
{
    /**
     * @param int $timestamp
     */
    public function setExpiresAt($timestamp);

    /**
     * @return int
     */
    public function getExpiresAt();

    /**
     * @param string $token
     */
    public function setToken($token);

    /**
     * @param string $scope
     */
    public function setScope($scope);

    public function setUser(UserInterface $user);

    public function getUser();

    public function setClient(Client $client);
}
