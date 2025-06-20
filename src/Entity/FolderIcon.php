<?php

namespace App\Entity;

use App\Repository\FolderIconRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: FolderIconRepository::class)]
#[ORM\Table(name: 'folder_icon')]
#[Vich\Uploadable]
class FolderIcon implements \Stringable
{
    public const string PUBLIC_PATH_TO_ICON_FOLDER = 'img/folder_icon';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'folder_icon', fileNameProperty: 'fileName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s/%s', self::PUBLIC_PATH_TO_ICON_FOLDER, $this->getFileName());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setImageFile(File|UploadedFile|null $imageFile = null): static
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPublicFilePath(): string
    {
        return sprintf('%s/%s', self::PUBLIC_PATH_TO_ICON_FOLDER, $this->getFileName());
    }
}
