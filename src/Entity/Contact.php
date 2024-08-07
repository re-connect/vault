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
        new Get(security: "is_granted('ROLE_OAUTH2_CONTACTS') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_CONTACTS') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_OAUTH2_BENEFICIARIES')", processor: PersonalDataStateProcessor::class),
        new Patch(security: "is_granted('UPDATE', object)"),
    ],
    normalizationContext: ['groups' => ['v3:contact:read']],
    denormalizationContext: ['groups' => ['v3:contact:write']],
    openapiContext: ['tags' => ['Contacts']],
)]
#[ApiResource(
    uriTemplate: '/beneficiaries/{id}/contacts',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'contacts',
            fromClass: Beneficiaire::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:contact:read']],
    denormalizationContext: ['groups' => ['v3:contact:write']],
    openapiContext: ['tags' => ['Contacts']],
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')",
)]
class Contact extends DonneePersonnelle
{
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('fr-fr.firstname')]
    private $prenom;
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('fr-fr.phone')]
    private $telephone;
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_EMAIL]])]
    private $email;
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private $commentaire;
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private $association;

    /**
     * Constructor.
     */
    public function __construct(?Beneficiaire $beneficiaire = null)
    {
        parent::__construct();
        $this->beneficiaire = $beneficiaire;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get prenom.
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set prenom.
     *
     * @param string $prenom
     *
     * @return Contact
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get telephone.
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set telephone.
     *
     * @param string $telephone
     *
     * @return Contact
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get commentaire.
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set commentaire.
     *
     * @param string $commentaire
     *
     * @return Contact
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get association.
     *
     * @return string
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * Set association.
     *
     * @param string $association
     *
     * @return Contact
     */
    public function setAssociation($association)
    {
        $this->association = $association;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s %s', $this->nom, $this->prenom);
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
            'prenom' => $this->prenom,
            'email' => $this->email,
            'commentaire' => $this->commentaire,
            'association' => $this->association,
            'telephone' => $this->telephone,
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
        ];
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->nom, $this->prenom);
    }
}
