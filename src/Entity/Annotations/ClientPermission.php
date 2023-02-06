<?php

namespace App\Entity\Annotations;

use App\Repository\ClientPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use League\Bundle\OAuth2ServerBundle\Model\Client;

/**
 * @ORM\Entity(repositoryClass=ClientPermissionRepository::class)
 * @ORM\Table(name="client_permission")
 */
class ClientPermission
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class)
     * @ORM\JoinColumn(nullable=false, referencedColumnName="identifier")
     */
    private Client $client;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $objectClass;

    /**
     * @ORM\Column(type="integer")
     */
    private int $objectId;

    /**
     * @ORM\Column(type="array")
     */
    private array $permissions = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setObjectClass(string $objectClass): self
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function setObjectId(int $objectId): self
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }
}
