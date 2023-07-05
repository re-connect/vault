<?php

namespace App\Entity;

use App\Entity\Traits\DeactivatableTrait;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseUser implements LegacyPasswordAuthenticatedUserInterface, UserInterface
{
    use DeactivatableTrait;

    protected $id;

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $usernameCanonical;

    /**
     * @var string
     */
    protected $emailCanonical;

    /**
     * The salt to use for hashing.
     *
     * @var string
     */
    protected $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string|null
     */
    protected $plainPassword;

    /**
     * @var \DateTime|null
     */
    protected $lastLogin;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @var string|null
     */
    protected $confirmationToken;

    /**
     * @var array
     */
    protected $roles;

    protected ?\DateTimeImmutable $passwordUpdatedAt;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->roles = [];
        $this->passwordUpdatedAt = new \DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function addRole($role)
    {
        $role = strtoupper($role);
        if ('ROLE_USER' === $role) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Gets the last login time.
     *
     * @return \DateTime|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        $this->usernameCanonical = $username;

        return $this;
    }

    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole('ROLE_SUPER_ADMIN');
        } else {
            $this->removeRole('ROLE_SUPER_ADMIN');
        }

        return $this;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function setLastLogin(\DateTime $time = null)
    {
        $this->lastLogin = $time;

        return $this;
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    public function getPasswordUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->passwordUpdatedAt;
    }

    public function setPasswordUpdatedAt(?\DateTimeImmutable $passwordUpdatedAt): BaseUser
    {
        $this->passwordUpdatedAt = $passwordUpdatedAt;

        return $this;
    }
}
