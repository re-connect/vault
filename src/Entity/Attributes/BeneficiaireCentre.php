<?php

namespace App\Entity\Attributes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'beneficiairecentre')]
#[ORM\Index(columns: ['centre_id'], name: 'IDX_6D65B3FC463CD7C3')]
#[ORM\Index(columns: ['beneficiaire_id'], name: 'IDX_6D65B3FC5AF81F68')]
#[ORM\Index(columns: ['initiateur_id'], name: 'IDX_6D65B3FC56D142FC')]
#[ApiResource(
    shortName: 'beneficiary_center',
    operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['v3:center:read']],
    denormalizationContext: ['groups' => ['v3:center:write']],
    openapiContext: ['tags' => ['Centres Beneficiaires']],
    security: "is_granted('ROLE_OAUTH2_CENTERS')",
)]
class BeneficiaireCentre extends UserCentre
{
    #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'beneficiairesCentres')]
    #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private Beneficiaire $beneficiaire;

    #[ORM\ManyToOne(targetEntity: Centre::class, inversedBy: 'beneficiairesCentres')]
    #[ORM\JoinColumn(name: 'centre_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private Centre $centre;

    #[ORM\OneToOne(mappedBy: 'beneficiaireCentre', targetEntity: ClientBeneficiaire::class, cascade: ['persist', 'remove'])]
    private ?ClientBeneficiaire $externalLink = null;

    #[\Override]
    public function getCentre(): Centre
    {
        return $this->centre;
    }

    public function setCentre(?Centre $centre = null): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function getBeneficiaire(): Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire = null): static
    {
        $this->beneficiaire = $beneficiaire;

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
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'beneficiaire' => $this->beneficiaire,
            'centre' => $this->centre->jsonSerializeSoft(),
            'b_valid' => $this->getBValid(),
        ];
    }

    public function getExternalLink(): ?ClientBeneficiaire
    {
        return $this->externalLink;
    }

    public function setExternalLink(?ClientBeneficiaire $externalLink): BeneficiaireCentre
    {
        $this->externalLink = $externalLink;
        $externalLink?->setBeneficiaireCentre($this);

        return $this;
    }

    #[\Override]
    public function setUser(User $user): static
    {
        $this->beneficiaire = $user->getSubjectBeneficiaire();

        return $this;
    }

    #[\Override]
    public function getUser(): User
    {
        return $this->beneficiaire?->getUser();
    }

    public function __clone()
    {
        $this->beneficiaire = clone $this->beneficiaire;
        $this->externalLink = null;
        $this->setInitiateur(null);
    }

    public static function createValid(Centre $relay): static
    {
        return (new BeneficiaireCentre())->setCentre($relay)->setBValid(true);
    }
}
