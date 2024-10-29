<?php

namespace App\Entity\Attributes;

use App\Repository\AnnexeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AnnexeRepository::class)]
#[Vich\Uploadable]
class Annexe implements \Stringable
{
    public const string SERVER_PATH_TO_IMAGE_FOLDER = 'uploads/annexe';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $url = null;

    #[Vich\UploadableField(mapping: 'annexe_fichier', fileNameProperty: 'fichier')]
    private File $fichierFile;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $fichier = null;

    #[ORM\Column(name: 'date_ajout', type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTime $dateAjout;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl($url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDateAjout(): \DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout($dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getActif(): bool
    {
        return $this->actif;
    }

    public function setActif($actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this::SERVER_PATH_TO_IMAGE_FOLDER.'/'.$this->getFichier();
    }

    public function getFichier(): string
    {
        return $this->fichier;
    }

    public function setFichier($fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getFichierFile(): File
    {
        return $this->fichierFile;
    }

    /**
     * @param File|UploadedFile $fichierFile
     */
    public function setFichierFile(?File $fichierFile = null)
    {
        $this->fichierFile = $fichierFile;
        if (null !== $fichierFile) {
            $this->dateAjout = new \DateTimeImmutable('now');
        }
    }
}
