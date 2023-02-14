<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BeneficiaireCentreRepository")
 */
// #[ApiResource(
//    shortName: 'beneficiary_center',
//    operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(), new Post()],
//    normalizationContext: ['groups' => ['v3:center:read']],
//    denormalizationContext: ['groups' => ['v3:center:write']],
//    openapiContext: ['tags' => ['Centres Beneficiaires']],
//    security: "is_granted('ROLE_OAUTH2_CENTERS')",
// )]
class BeneficiaireCentre extends UserCentre
{
    /**
     * @var Centre
     */
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private $centre;
    /**
     * @var Beneficiaire
     */
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private $beneficiaire;
    private ?ClientBeneficiaire $externalLink = null;

    /**
     * Get centre.
     *
     * @return Centre
     */
    public function getCentre()
    {
        return $this->centre;
    }

    /**
     * Set centre.
     *
     * @return BeneficiaireCentre
     */
    public function setCentre(Centre $centre = null)
    {
        $this->centre = $centre;

        return $this;
    }

    /**
     * Get beneficiaire.
     *
     * @return Beneficiaire
     */
    public function getBeneficiaire()
    {
        return $this->beneficiaire;
    }

    /**
     * Set beneficiaire.
     *
     * @return BeneficiaireCentre
     */
    public function setBeneficiaire(Beneficiaire $beneficiaire = null)
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

    public function setUser(User $user): self
    {
        $this->beneficiaire = $user->getSubjectBeneficiaire();

        return $this;
    }

    public function __clone()
    {
        $this->beneficiaire = clone $this->beneficiaire;
        $this->externalLink = null;
        $this->setInitiateur(null);
    }

    public static function createValid(Centre $relay): self
    {
        return (new BeneficiaireCentre())->setCentre($relay)->setBValid(true);
    }
}
