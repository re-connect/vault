<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ClientRepository")
 */
class Client extends BaseClient
{
    public const CLIENT_APPLI_MOBILE = 'applimobile';
    public const CLIENT_ROSALIE = 'rosalie';
    public const CLIENT_ROSALIE_NEW = 'rosalieapp';
    public const SERVER_PATH_TO_IMAGE_FOLDER = 'uploads/client';
    public const CLIENT_REDIRECT_URI = '/user/redirect-user/';

    public const ACCESS_ALL = 'access.all';
    public const ACCESS_DOCUMENT_ALL = 'access.document.all';
    public const ACCESS_DOCUMENT_READ = 'access.document.read';
    public const ACCESS_DOCUMENT_WRITE = 'access.document.write';
    public const ACCESS_DOCUMENT_DELETE = 'access.document.delete';
    public const ACCESS_EVENEMENT_ALL = 'access.evenement.all';
    public const ACCESS_EVENEMENT_READ = 'access.evenement.read';
    public const ACCESS_EVENEMENT_WRITE = 'access.evenement.write';
    public const ACCESS_EVENEMENT_DELETE = 'access.evenement.delete';
    public const ACCESS_NOTE_ALL = 'access.note.all';
    public const ACCESS_NOTE_READ = 'access.note.read';
    public const ACCESS_NOTE_WRITE = 'access.note.write';
    public const ACCESS_NOTE_DELETE = 'access.note.delete';
    public const ACCESS_CONTACT_ALL = 'access.contact.all';
    public const ACCESS_CONTACT_READ = 'access.contact.read';
    public const ACCESS_CONTACT_WRITE = 'access.contact.write';
    public const ACCESS_CONTACT_DELETE = 'access.contact.delete';
    public const ACCESS_BENEFICIAIRE_ALL = 'access.beneficiaire.all';
    public const ACCESS_BENEFICIAIRE_READ = 'access.beneficiaire.read';
    public const ACCESS_BENEFICIAIRE_WRITE = 'access.beneficiaire.write';
    public const ACCESS_BENEFICIAIRE_WRITE_WITH_PASSWORD = 'access.beneficiaire.write.with_password';
    public const ACCESS_BENEFICIAIRE_DELETE = 'access.beneficiaire.delete';
    public const ACCESS_USER_ALL = 'access.user.all';
    public const ACCESS_USER_READ = 'access.user.read';
    public const ACCESS_USER_WRITE = 'access.user.write';
    public const ACCESS_USER_DELETE = 'access.user.delete';
    public const ACCESS_MEMBRE_ALL = 'access.membre.all';
    public const ACCESS_MEMBRE_READ = 'access.membre.read';
    public const ACCESS_MEMBRE_WRITE = 'access.membre.write';
    public const ACCESS_MEMBRE_DELETE = 'access.membre.delete';
    public const ACCESS_CENTRE_ALL = 'access.centre.all';
    public const ACCESS_CENTRE_READ = 'access.centre.read';
    public const ACCESS_CENTRE_WRITE = 'access.centre.write';
    public const ACCESS_CENTRE_DELETE = 'access.centre.delete';

    /**
     * @Vich\UploadableField(mapping="client_dossier_image", fileNameProperty="dossierImage")
     *
     * @var File
     */
    private $dossierFile;

    /**
     * @var string
     */
    private $nom;

    /** @var string */
    private $dossierNom;

    /** @var bool */
    private $actif;

    /** @var string */
    private $dossierImage;

    /** @var string */
    private $newClientIdentifier;

    /**
     * @var array
     */
    private $access = [];

    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Set nom.
     *
     * @param string $nom
     */
    public function setNom($nom): Client
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDossierNom(): ?string
    {
        return $this->dossierNom;
    }

    /**
     * Set dossierNom.
     *
     * @param string $dossierNom
     */
    public function setDossierNom($dossierNom): Client
    {
        $this->dossierNom = $dossierNom;

        return $this;
    }

    public function isNew(): bool
    {
        return null === $this->getId();
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    /**
     * Set actif.
     *
     * @param bool $actif
     */
    public function setActif($actif): Client
    {
        $this->actif = $actif;

        return $this;
    }

    public function getDossierImage(): ?string
    {
        return $this->dossierImage;
    }

    public function setDossierImage(?string $dossierImage = null): self
    {
        $this->dossierImage = $dossierImage;

        return $this;
    }

    /**
     * @return File|UploadedFile
     */
    public function getDossierFile(): ?File
    {
        return $this->dossierFile;
    }

    public function setDossierFile(File $dossierFile): void
    {
        $this->dossierFile = $dossierFile;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->nom;
    }

    /**
     * Get access.
     *
     * @return array
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set access.
     *
     * @param array $access
     *
     * @return Client
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    public function getNewClientIdentifier(): ?string
    {
        return $this->newClientIdentifier;
    }

    public function setNewClientIdentifier(?string $newClientIdentifier): self
    {
        $this->newClientIdentifier = $newClientIdentifier;

        return $this;
    }
}
