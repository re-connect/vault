<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\State\PersonalDataStateProcessor;
use App\Domain\Anonymization\AnonymizationHelper;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Delete(security: "is_granted('UPDATE', object)"),
        new Get(security: "is_granted('ROLE_OAUTH2_NOTES') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_NOTES') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')", processor: PersonalDataStateProcessor::class),
        new Patch(security: "is_granted('UPDATE', object)"),
    ],
    normalizationContext: ['groups' => ['v3:note:read']],
    denormalizationContext: ['groups' => ['v3:note:write']],
    openapiContext: ['tags' => ['Notes']],
)]
#[ApiResource(
    uriTemplate: '/beneficiaries/{id}/notes',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'notes',
            fromClass: Beneficiaire::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:note:read']],
    denormalizationContext: ['groups' => ['v3:note:write']],
    openapiContext: ['tags' => ['Notes']],
    security: "is_granted('ROLE_OAUTH2_NOTES')",
)]
class Note extends DonneePersonnelle
{
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:note:write', 'v3:note:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private $contenu;

    public function __construct(?Beneficiaire $beneficiaire = null)
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
    #[\Override]
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
