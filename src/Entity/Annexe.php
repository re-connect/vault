<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 *
 * @method upload()
 */
class Annexe
{
    public const SERVER_PATH_TO_IMAGE_FOLDER = 'uploads/annexe';
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $url;

    /**
     * @Vich\UploadableField(mapping="annexe_fichier", fileNameProperty="fichier")
     *
     * @var File
     */
    private $fichierFile;

    /** @var string */
    private $fichier;

    /**
     * @var \DateTime
     */
    private $dateAjout;
    /**
     * @var bool
     */
    private $actif = true;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Lifecycle callback to upload the file to the server.
     */
    public function lifecycleFileUpload(): void
    {
        $this->upload();
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Annexe
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get dateAjout.
     *
     * @return \DateTime
     */
    public function getDateAjout()
    {
        return $this->dateAjout;
    }

    /**
     * Set dateAjout.
     *
     * @param \DateTime $dateAjout
     *
     * @return Annexe
     */
    public function setDateAjout($dateAjout)
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    /**
     * Get actif.
     *
     * @return bool
     */
    public function getActif()
    {
        return $this->actif;
    }

    /**
     * Set actif.
     *
     * @param bool $actif
     *
     * @return Annexe
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    public function __toString()
    {
        return $this::SERVER_PATH_TO_IMAGE_FOLDER.'/'.$this->getFichier();
    }

    /**
     * Get fichier.
     *
     * @return string
     */
    public function getFichier()
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
    public function setFichier($fichier)
    {
        $this->fichier = $fichier;

        return $this;
    }

    /**
     * @return File
     */
    public function getFichierFile()
    {
        return $this->fichierFile;
    }

    /**
     * @param File|UploadedFile $fichierFile
     */
    public function setFichierFile(File $fichierFile = null)
    {
        $this->fichierFile = $fichierFile;
        if (null !== $fichierFile) {
            $this->dateAjout = new \DateTimeImmutable('now');
        }
    }
}
