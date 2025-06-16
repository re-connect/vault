<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ClientBeneficiaire extends ClientEntity
{
    #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'externalLink')]
    protected mixed $entity = null;

    #[ORM\ManyToOne(targetEntity: BeneficiaireCentre::class, inversedBy: 'externalLink')]
    #[ORM\JoinColumn(name: 'beneficiaire_centre_id', referencedColumnName: 'id')]
    private ?BeneficiaireCentre $beneficiaireCentre = null;

    #[ORM\Column(name: 'membre_distant_id', type: 'integer', nullable: true, options: ['unsigned' => true, 'comment' => 'Identifier of the external initiator member (No entity link).'])]
    private ?int $membreDistantId = null;

    public function setEntity(?Beneficiaire $entity = null): ClientBeneficiaire
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?Beneficiaire
    {
        return $this->entity;
    }

    public function getBeneficiaireCentre(): ?BeneficiaireCentre
    {
        return $this->beneficiaireCentre;
    }

    public function setBeneficiaireCentre(?BeneficiaireCentre $beneficiaireCentre): ClientBeneficiaire
    {
        $this->beneficiaireCentre = $beneficiaireCentre;

        return $this;
    }

    public function getMembreDistantId(): ?int
    {
        return $this->membreDistantId;
    }

    public function setMembreDistantId(?int $membreDistantId): ClientBeneficiaire
    {
        $this->membreDistantId = $membreDistantId;

        return $this;
    }

    public static function createForMember(Client $client, string $externalId, ?int $memberExternalId = null): self
    {
        return (new ClientBeneficiaire($client, $externalId))->setMembreDistantId($memberExternalId);
    }
}
