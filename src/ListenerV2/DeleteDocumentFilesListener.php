<?php

namespace App\ListenerV2;

use App\Entity\Document;
use App\ServiceV2\BucketService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Document::class)]
#[AsDoctrineListener(event: Events::postFlush)]
#[AsDoctrineListener(event: Events::onClear)]
class DeleteDocumentFilesListener
{
    /** @var list<string> */
    private array $keysToDelete = [];

    public function __construct(private readonly BucketService $bucketService)
    {
    }

    public function postRemove(Document $document): void
    {
        foreach ([$document->getObjectKey(), $document->getThumbnailKey()] as $key) {
            if ($key) {
                $this->keysToDelete[] = $key;
            }
        }
    }

    // Files are deleted once the transaction is committed, so a rollback can not leave a row without its files
    public function postFlush(PostFlushEventArgs $args): void
    {
        $keys = $this->keysToDelete;
        $this->keysToDelete = [];

        foreach ($keys as $key) {
            try {
                $this->bucketService->deleteFile($key);
            } catch (\Exception) {
                // BucketService already logs it: a storage failure must not undo the deletion
            }
        }
    }

    // A failed flush closes the entity manager: the rows are still there, so their files must be kept
    public function onClear(OnClearEventArgs $args): void
    {
        $this->keysToDelete = [];
    }
}
