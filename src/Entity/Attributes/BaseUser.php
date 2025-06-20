<?php

namespace App\Entity\Attributes;

use App\Entity\Traits\DeactivatableTrait;
use App\Validator\Constraints as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class BaseUser implements LegacyPasswordAuthenticatedUserInterface, UserInterface, \Stringable
{
    use DeactivatableTrait;

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(name: 'username', type: 'string', length: 180, nullable: false)]
    protected $username = '';

    #[ORM\Column(name: 'username_canonical', type: 'string', length: 180, nullable: false)]
    #[Anonymize('md5')]
    protected $usernameCanonical;

    #[ORM\Column(name: 'emailCanonical', type: 'string', length: 255, nullable: true)]
    #[Anonymize('email', options: ['domain' => 'yopmail.com'])]
    protected $emailCanonical;

    #[ORM\Column(name: 'salt', type: 'string', length: 255, nullable: true)]
    protected $salt;

    #[ORM\Column(name: 'password', type: 'string', length: 255, nullable: false)]
    protected $password;

    #[CustomAssert\PasswordCriteria(groups: ['password', 'password-admin'])]
    #[Groups(['v3:user:write', 'v3:user:read'])]
    protected $plainPassword;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    protected $lastLogin;

    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 180, nullable: true)]
    protected $confirmationToken;

    #[ORM\Column(name: 'roles', type: 'array', length: 0, nullable: false)]
    protected $roles;

    #[ORM\Column(name: 'password_updated_at', type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $passwordUpdatedAt;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->roles = [];
        $this->passwordUpdatedAt = new \DateTimeImmutable();
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->username;
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function addRole($role): static
    {
        $role = strtoupper((string) $role);
        if ('ROLE_USER' === $role) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    #[\Override]
    public function eraseCredentials(): void
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

    #[\Override]
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    #[\Override]
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

    public function getLastLoginToString(): ?string
    {
        return $this->lastLogin?->format('d/m/Y H:i');
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    #[\Override]
    public function getRoles(): array
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function hasRole($role): bool
    {
        return in_array(strtoupper((string) $role), $this->getRoles(), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function removeRole($role): static
    {
        if (false !== $key = array_search(strtoupper((string) $role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setUsername($username): static
    {
        $this->username = $username;
        $this->usernameCanonical = $username;

        return $this;
    }

    public function setUsernameCanonical($usernameCanonical): static
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function setSalt($salt): static
    {
        $this->salt = $salt;

        return $this;
    }

    public function setEmailCanonical($emailCanonical): static
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function setPassword($password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setSuperAdmin($boolean): static
    {
        if (true === $boolean) {
            $this->addRole('ROLE_SUPER_ADMIN');
        } else {
            $this->removeRole('ROLE_SUPER_ADMIN');
        }

        return $this;
    }

    public function setPlainPassword($password): static
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function setLastLogin(?\DateTime $time = null): static
    {
        $this->lastLogin = $time;

        return $this;
    }

    public function setConfirmationToken($confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function isEqualTo(UserInterface $user): bool
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
