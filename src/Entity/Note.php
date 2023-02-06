<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(), new Post()],
    normalizationContext: ['groups' => ['v3:note:read']],
    denormalizationContext: ['groups' => ['v3:note:write']],
    openapiContext: ['tags' => ['Notes']],
    security: "is_granted('ROLE_OAUTH2_NOTES')",
)]
class Note extends DonneePersonnelle
{
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:note:write', 'v3:note:read'])]
    private $contenu;

    /**
     * @param Beneficiaire $beneficiaire
     */
    public function __construct($beneficiaire)
    {
        parent::__construct();
        $this->beneficiaire = $beneficiaire;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get contenu.
     *
     * @return string
     */
    public function getContenu()
    {
        return $this->contenu;
    }

    /**
     * Set contenu.
     *
     * @param string $contenu
     *
     * @return Note
     */
    public function setContenu($contenu)
    {
        $this->contenu = $contenu;

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
            'id' => $this->id,
            'b_prive' => $this->bPrive,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'contenu' => $this->contenu,
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
        ];
    }
}
