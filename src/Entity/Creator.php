<?php

namespace App\Entity;

abstract class Creator
{
    private ?int $id = null;

    private ?Document $document = null;

    private ?User $user = null;

    private ?Note $note = null;

    private ?Evenement $evenement = null;

    private ?Contact $contact = null;

    private ?Dossier $dossier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(Document $document = null): self
    {
        $this->document = $document;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(Note $note = null): self
    {
        $this->note = $note;

        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement = null): self
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact = null): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(Dossier $dossier = null): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function setPersonalData(DonneePersonnelle $personalData): self
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
