<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
class Partenaire
{
    use GedmoTimedTrait;

    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $nom;
    /**
     * @var string
     */
    private $imageName;
    /**
     * @var string
     */
    private $link;
    private bool $canada = false;
    private $imageFile;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param File|UploadedFile $image
     *
     * @throws \Exception
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;
        if ($image) {
            $this->updatedAt = new \DateTime('now');
        }
    }

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
     * Get nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set nom.
     *
     * @param string $nom
     *
     * @return Partenaire
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get imageName.
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Set imageName.
     *
     * @param string $imageName
     *
     * @return Partenaire
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get link.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set link.
     *
     * @param string $link
     *
     * @return Partenaire
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    public function isCanada(): bool
    {
        return $this->canada;
    }

    public function setCanada(bool $canada): self
    {
        $this->canada = $canada;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->canada = true;
        }
    }
}
