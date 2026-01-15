<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'client_entity')]
#[ORM\Index(columns: ['entity_id'], name: 'IDX_5B8E0FDB81257D5D')]
#[ORM\Index(columns: ['beneficiaire_centre_id'], name: 'IDX_5B8E0FDBF15C33B')]
#[ORM\Index(columns: ['client_id'], name: 'IDX_5B8E0FDB19EB6921')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'clientCentre' => ClientCentre::class,
    'clientMembre' => ClientMembre::class,
    'clientBeneficiaire' => ClientBeneficiaire::class,
    'clientGestionnaire' => ClientGestionnaire::class,
])]
abstract class ClientEntity implements \Stringable
{
    use TimestampableEntity;

    protected mixed $entity;

    #[ORM\Column(name: 'distant_id', type: 'string', length: 255, options: ['unsigned' => true])]
    #[ORM\Id]
    private int|string|null $distantId;

    #[ORM\Column(name: 'entity_name', type: 'string', length: 255, nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    protected mixed $entity_name;

    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne(targetEntity: Client::class)]
        #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
        private ?Client $client = null,
        $distantId = null,
    ) {
        $this->distantId = (string) $distantId;
        $this->entity_name = (new \ReflectionClass($this))->getShortName();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getClient()->getNom().': '.$this->getDistantId();
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

    public function setClient(?Client $client = null): self
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

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdateAt(?\DateTime $updatedAt = null): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
