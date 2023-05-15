<?php

namespace App\Entity;

class ClientBeneficiaire extends ClientEntity
{
    private ?BeneficiaireCentre $beneficiaireCentre = null;

    private ?int $membreDistantId = null;

    public function setEntity(Beneficiaire $entity = null): ClientBeneficiaire
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

    public static function createForMember(Client $client, string $externalId, int $memberExternalId = null): self
    {
        return (new ClientBeneficiaire($client, $externalId))->setMembreDistantId($memberExternalId);
    }
}
