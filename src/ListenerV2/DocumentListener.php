<?php

namespace App\ListenerV2;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Document::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Document::class)]
class DocumentListener
{
    public function __construct(private readonly MimeTypesInterface $mimeTypes)
    {
    }

    public function preUpdate(Document $document, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('nom')) {
            $this->sanitizeFilename($document);
        }
    }

    public function prePersist(Document $document, PrePersistEventArgs $args): void
    {
        $this->sanitizeFilename($document);
    }

    private function sanitizeFilename(Document $document): void
    {
        $pathInfo = pathinfo((string) $document->getNom());
        $name = $pathInfo['filename'];

        $dirname = $pathInfo['dirname'] ?? null;
        if ($dirname && '.' !== $dirname) {
            $name = sprintf('%s/%s', $dirname, $name);
        }

        $documentExtension = $document->getExtension();
        $extension = $pathInfo['extension'] ?? null;
        if ($extension && !$this->isSameFileType($extension, $documentExtension)) {
            $name = sprintf('%s.%s', $name, $extension);
        }

        $sanitizedName = (new AsciiSlugger())->slug($name, '_')->toString();

        if ($documentExtension) {
            $sanitizedName = sprintf('%s.%s', $sanitizedName, $document->getExtension());
        }

        $document->setNom($sanitizedName);
    }

    /**
     * Aliases such as "jpeg" and "jpg" share a MIME type: keeping both would give "photo_jpeg.jpg".
     */
    private function isSameFileType(string $extension, ?string $documentExtension): bool
    {
        if ($extension === $documentExtension) {
            return true;
        }

        $sharedMimeTypes = array_intersect(
            $this->mimeTypes->getMimeTypes($extension),
            $this->mimeTypes->getMimeTypes((string) $documentExtension),
        );

        return [] !== $sharedMimeTypes;
    }
}
