<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\ApiOperations;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\State\PersonalDataStateProcessor;
use App\Domain\Anonymization\AnonymizationHelper;
use App\Entity\Interface\ClientResourceInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'contact')]
#[ORM\Index(columns: ['deposePar_id'], name: 'IDX_4C62E638F2AB781')]
#[ApiResource(
    operations: [
        new Delete(security: "is_granted('UPDATE', object) and is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_OAUTH2_CONTACTS_READ') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_CONTACTS_READ') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_OAUTH2_CONTACTS_CREATE')", processor: PersonalDataStateProcessor::class),
        new Patch(security: "is_granted('UPDATE', object) and is_granted('ROLE_USER')"),
        new Patch(
            uriTemplate: '/contacts/{id}/toggle-visibility',
            security: "is_granted('UPDATE', object)",
            name: ApiOperations::TOGGLE_VISIBILITY.'_contact',
            processor: PersonalDataStateProcessor::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:contact:read']],
    denormalizationContext: ['groups' => ['v3:contact:write']],
    openapi: new Operation(
        tags: ['Contacts'],
    ),
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
    openapi: new Operation(
        tags: ['Contacts'],
    ),
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES_READ')",
)]
class Contact extends DonneePersonnelle implements ClientResourceInterface
{
    public const TOGGLE_VISIBILITY_API_ROUTE = 'ToggleVisibility';
    #[ORM\Column(name: 'prenom', type: 'string', length: 255, nullable: false)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('fr-fr.firstname')]
    #[Assert\NotBlank]
    private ?string $prenom = null;

    #[ORM\Column(name: 'telephone', type: 'string', length: 255, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('fr-fr.phone')]
    private ?string $telephone = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_EMAIL]])]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(name: 'commentaire', type: 'text', length: 0, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'association', type: 'string', length: 255, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:contact:write', 'v3:contact:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private ?string $association = null;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Creator::class, cascade: ['persist', 'remove'])]
    protected Collection $creators;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'contacts')]
        #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
        protected ?Beneficiaire $beneficiaire = null)
    {
        parent::__construct();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom = null): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getAssociation(): ?string
    {
        return $this->association;
    }

    public function setAssociation(?string $association): static
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

    #[\Override]
    public function getExternalLinks(): Collection
    {
        return $this->beneficiaire?->getExternalLinks();
    }

    #[\Override]
    public function hasExternalLinkForClient(Client $client): bool
    {
        return $this->beneficiaire?->hasExternalLinkForClient($client);
    }

    #[\Override]
    public function getExternalLinkForClient(Client $client): ?ClientEntity
    {
        return $this->beneficiaire?->getExternalLinkForClient($client);
    }

    #[\Override]
    public function getScopeName(): string
    {
        return 'CONTACTS';
    }
}
