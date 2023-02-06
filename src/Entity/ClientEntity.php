<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

abstract class ClientEntity
{
    protected mixed $entity;

    protected ?string $entity_name = null;

    #[Groups(['v3:beneficiary:write'])]
    private null|int|string $distantId;

    private ?Client $client;

    private ?\DateTime $createdAt = null;

    private ?\DateTime $updateAt = null;

    public function __construct($client = null, $distantId = null)
    {
        $this->client = $client;
        $this->distantId = (string) $distantId;
        $this->entity_name = (new \ReflectionClass($this))->getShortName();
    }

    public function getDistantId(): string
    {
        return (string) $this->distantId;
    }

    public function setDistantId(int|string|null $distantId): self
    {
        $this->distantId = (string) $distantId;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client = null): self
    {
        $this->client = $client;

        return $this;
    }

    public function getEntityName(): string
    {
        return $this->entity_name;
    }

    public function setEntityName(?string $entityName = null): self
    {
        $this->entity_name = $entityName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTime $updateAt = null): self
    {
        $this->updateAt = $updateAt;

        return $this;
    }
}
