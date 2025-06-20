<?php

namespace App\Entity\Attributes;

use App\Repository\AnnexeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     *
     * @return Annexe
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get dateAjout.
     *
     * @return \DateTime
     */
    public function getDateAjout(): \DateTime
    {
        return $this->dateAjout;
    }

    /**
     * Set dateAjout.
     *
     *
     * @return Annexe
     */
    public function setDateAjout(\DateTime $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    /**
     * Get actif.
     *
     * @return bool
     */
    public function getActif(): bool
    {
        return $this->actif;
    }

    /**
     * Set actif.
     *
     *
     * @return Annexe
     */
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

    /**
     * Get fichier.
     *
     * @return string
     */
    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    /**
     * Set fichier.
     *
     * @param string $fichier
     *
     * @return Annexe
     */
    public function setFichier(?string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    /**
     * @return File
     */
    public function getFichierFile(): ?File
    {
        return $this->fichierFile;
    }

    /**
     * @param File|UploadedFile $fichierFile
     */
    public function setFichierFile(?File $fichierFile = null): void
    {
        $this->fichierFile = $fichierFile;
        if (null !== $fichierFile) {
            $this->dateAjout = new \DateTime('now');
        }
    }
}
