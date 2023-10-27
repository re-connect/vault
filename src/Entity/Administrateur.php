<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;

/**
 * Administrateur.
 */
class Administrateur extends Subject
{
    use GedmoTimedTrait;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Set user.
     *
     * @return Administrateur
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_ADMINISTRATEUR);

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->user = null;
        }
    }
}
