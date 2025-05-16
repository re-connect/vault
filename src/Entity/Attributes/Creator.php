<?php

namespace App\Entity\Attributes;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'creator')]
#[ORM\Index(columns: ['document_id'], name: 'IDX_BC06EA63C33F7837')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_BC06EA63A76ED395')]
#[ORM\Index(columns: ['note_id'], name: 'IDX_BC06EA6326ED0855')]
#[ORM\Index(columns: ['evenement_id'], name: 'IDX_BC06EA63FD02F13')]
#[ORM\Index(columns: ['contact_id'], name: 'IDX_BC06EA63E7A1254A')]
#[ORM\Index(columns: ['entity_id'], name: 'IDX_BC06EA6381257D5D')]
#[ORM\Index(columns: ['dossier_id'], name: 'IDX_BC06EA63611C0C56')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'creatorClient' => CreatorClient::class,
    'creatorUser' => CreatorUser::class,
    'creatorCenter' => CreatorCentre::class,
])]
abstract class Creator
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id')]
    private ?Document $document = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'note_id', referencedColumnName: 'id')]
    private ?Note $note = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id')]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id')]
    private ?Dossier $dossier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document = null): static
    {
        $this->document = $document;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note = null): static
    {
        $this->note = $note;

        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement = null): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact = null): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier = null): static
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function setPersonalData(DonneePersonnelle $personalData): static
    {
        if ($personalData instanceof Contact) {
            $this->setContact($personalData);
        } elseif ($personalData instanceof Note) {
            $this->setNote($personalData);
        } elseif ($personalData instanceof Evenement) {
            $this->setEvenement($personalData);
        } elseif ($personalData instanceof Document) {
            $this->setDocument($personalData);
        } elseif ($personalData instanceof Dossier) {
            $this->setDossier($personalData);
        }

        return $this;
    }
}
