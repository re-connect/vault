<?php

namespace App\Entity\Attributes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\State\PersonalDataStateProcessor;
use App\Domain\Anonymization\AnonymizationHelper;
use App\Entity\Creator;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'note')]
#[ORM\Index(columns: ['deposePar_id'], name: 'IDX_CFBDFA14F2AB781')]
#[ORM\Index(columns: ['beneficiaire_id'], name: 'IDX_CFBDFA145AF81F68')]
#[ApiResource(
    operations: [
        new Delete(security: "is_granted('UPDATE', object)"),
        new Get(security: "is_granted('ROLE_OAUTH2_NOTES') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_NOTES') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_OAUTH2_BENEFICIARIES')", processor: PersonalDataStateProcessor::class),
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
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')",
)]
class Note extends DonneePersonnelle
{
    #[ORM\Column(name: 'contenu', type: 'text', length: 0, nullable: false)]
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:note:write', 'v3:note:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    #[Assert\NotBlank]
    private ?string $contenu = null;

    #[ORM\OneToMany(mappedBy: 'note', targetEntity: Creator::class, cascade: ['persist', 'remove'])]
    protected Collection $creators;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'notes')]
        #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
        protected ?Beneficiaire $beneficiaire = null
    ) {
        parent::__construct();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

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
