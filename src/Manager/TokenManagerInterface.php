<?php

namespace App\Manager;

use App\Entity\TokenInterface;

interface TokenManagerInterface
{
    /**
     * Create a new TokenInterface.
     *
     * @return TokenInterface
     */
    public function createToken();

    /**
     * Return the class name of the Token.
     *
     * @return string
     */
    public function getClass();

    /**
     * Retrieve a token using a set of criteria.
     *
     * @return TokenInterface|null
     */
    public function findTokenBy(array $criteria);

    /**
     * Retrieve a token (object) by its token string.
     *
     * @param string $token a token
     *
     * @return TokenInterface|null
     */
    public function findTokenByToken($token);

    /**
     * Save or update a given token.
     *
     * @param TokenInterface $token the token to save or update
     */
    public function updateToken(TokenInterface $token);

    /**
     * Delete a given token.
     *
     * @param TokenInterface $token the token to delete
     */
    public function deleteToken(TokenInterface $token);

    /**
     * Delete expired tokens.
     *
     * @return int the number of tokens deleted
     */
    public function deleteExpired();
}
