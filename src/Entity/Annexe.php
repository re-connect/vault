<?php

namespace App\Entity;

use App\Repository\AnnexeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: 'annexe')]
#[ORM\UniqueConstraint(name: 'UNIQ_1BB35BA29B76551F', columns: ['fichier'])]
#[ORM\UniqueConstraint(name: 'UNIQ_1BB35BA2F47645AE', columns: ['url'])]
#[ORM\Entity(repositoryClass: AnnexeRepository::class)]
#[Vich\Uploadable]
class Annexe implements \Stringable
{
    public const string SERVER_PATH_TO_IMAGE_FOLDER = 'uploads/annexe';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: false)]
    private string $url;

    #[Vich\UploadableField(mapping: 'annexe_fichier', fileNameProperty: 'fichier')]
    private ?File $fichierFile = null;

    #[ORM\Column(name: 'fichier', type: 'string', length: 255, nullable: false)]
    private ?string $fichier = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'date_ajout', type: 'datetime', nullable: false)]
    private \DateTime $dateAjout;

    #[ORM\Column(name: 'actif', type: 'boolean', nullable: false, options: ['default' => '1'])]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDateAjout(): \DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTime $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this::SERVER_PATH_TO_IMAGE_FOLDER.'/'.$this->getFichier();
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(?string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getFichierFile(): ?File
    {
        return $this->fichierFile;
    }

    public function setFichierFile(?File $fichierFile = null): void
    {
        $this->fichierFile = $fichierFile;
        if (null !== $fichierFile) {
            $this->dateAjout = new \DateTime('now');
        }
    }
}
