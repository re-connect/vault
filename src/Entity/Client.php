<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'client')]
class Client extends BaseClient implements \Stringable
{
    public const string CLIENT_APPLI_MOBILE = 'applimobile';
    public const string CLIENT_ROSALIE = 'rosalie';
    public const string CLIENT_RECONNECT_PRO = 'reconnect_pro';
    public const string CLIENT_ROSALIE_NEW = 'rosalieapp';
    public const string SERVER_PATH_TO_IMAGE_FOLDER = 'uploads/client';
    public const string CLIENT_REDIRECT_URI = '/user/redirect-user/';

    public const string ACCESS_ALL = 'access.all';
    public const string ACCESS_DOCUMENT_ALL = 'access.document.all';
    public const string ACCESS_DOCUMENT_READ = 'access.document.read';
    public const string ACCESS_DOCUMENT_WRITE = 'access.document.write';
    public const string ACCESS_DOCUMENT_DELETE = 'access.document.delete';
    public const string ACCESS_EVENEMENT_ALL = 'access.evenement.all';
    public const string ACCESS_EVENEMENT_READ = 'access.evenement.read';
    public const string ACCESS_EVENEMENT_WRITE = 'access.evenement.write';
    public const string ACCESS_EVENEMENT_DELETE = 'access.evenement.delete';
    public const string ACCESS_NOTE_ALL = 'access.note.all';
    public const string ACCESS_NOTE_READ = 'access.note.read';
    public const string ACCESS_NOTE_WRITE = 'access.note.write';
    public const string ACCESS_NOTE_DELETE = 'access.note.delete';
    public const string ACCESS_CONTACT_ALL = 'access.contact.all';
    public const string ACCESS_CONTACT_READ = 'access.contact.read';
    public const string ACCESS_CONTACT_WRITE = 'access.contact.write';
    public const string ACCESS_CONTACT_DELETE = 'access.contact.delete';
    public const string ACCESS_BENEFICIAIRE_ALL = 'access.beneficiaire.all';
    public const string ACCESS_BENEFICIAIRE_READ = 'access.beneficiaire.read';
    public const string ACCESS_BENEFICIAIRE_WRITE = 'access.beneficiaire.write';
    public const string ACCESS_BENEFICIAIRE_WRITE_WITH_PASSWORD = 'access.beneficiaire.write.with_password';
    public const string ACCESS_BENEFICIAIRE_DELETE = 'access.beneficiaire.delete';
    public const string ACCESS_USER_ALL = 'access.user.all';
    public const string ACCESS_USER_READ = 'access.user.read';
    public const string ACCESS_USER_WRITE = 'access.user.write';
    public const string ACCESS_USER_DELETE = 'access.user.delete';
    public const string ACCESS_MEMBRE_ALL = 'access.membre.all';
    public const string ACCESS_MEMBRE_READ = 'access.membre.read';
    public const string ACCESS_MEMBRE_WRITE = 'access.membre.write';
    public const string ACCESS_MEMBRE_DELETE = 'access.membre.delete';
    public const string ACCESS_CENTRE_ALL = 'access.centre.all';
    public const string ACCESS_CENTRE_READ = 'access.centre.read';
    public const string ACCESS_CENTRE_WRITE = 'access.centre.write';
    public const string ACCESS_CENTRE_DELETE = 'access.centre.delete';

    #[Vich\UploadableField(mapping: 'client_dossier_image', fileNameProperty: 'dossierImage')]
    private ?File $dossierFile = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'dossier_nom', type: 'string', length: 255, nullable: true)]
    private ?string $dossierNom = null;

    #[ORM\Column(name: 'dossier_image', type: 'string', length: 255, nullable: true)]
    private ?string $dossierImage = null;

    #[ORM\Column(name: 'actif', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(name: 'access', type: 'array', nullable: false)]
    private array $access = [];

    #[ORM\Column(name: 'newClientIdentifier', type: 'string', length: 255, nullable: true)]
    private ?string $newClientIdentifier = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): Client
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDossierNom(): ?string
    {
        return $this->dossierNom;
    }

    public function setDossierNom(?string $dossierNom): Client
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

    public function setActif(bool $actif): Client
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

    public function getDossierFile(): ?File
    {
        return $this->dossierFile;
    }

    public function setDossierFile(File $dossierFile): void
    {
        $this->dossierFile = $dossierFile;
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->nom;
    }

    public function getAccess(): array
    {
        return $this->access;
    }

    public function setAccess(array $access): static
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

    public function allowsMultipleLinks(): bool
    {
        return self::CLIENT_RECONNECT_PRO === $this->getNom();
    }
}
