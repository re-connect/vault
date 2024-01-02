<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\String\u;

class DocumentManager
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly FlysystemS3Client $s3Client,
        private readonly DocumentRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly BucketService $bucketService,
    ) {
    }

    /**
     * @return Document[]
     */
    public function getDocumentsWithUrl(Beneficiaire $beneficiary, Dossier $folder = null, string $search = null): array
    {
        return $this->hydrateDocumentsAndThumbnailWithUrl(
            $this->repository->findByBeneficiary(
                $beneficiary,
                $this->isLoggedInUser($beneficiary->getUser()),
                $folder,
                $search,
            )
        );
    }

    /**
     * @param Document[] $documents
     *
     * @return Document[]
     */
    public function hydrateDocumentsAndThumbnailWithUrl(array $documents): array
    {
        return array_map(fn (Document $document) => $this->hydrateDocumentAndThumbnailWithUrl($document), $documents);
    }

    public function hydrateDocumentAndThumbnailWithUrl(Document $document): Document
    {
        $document->setPresignedUrl($this->s3Client->getPresignedUrl($document->getObjectKey()));
        if ($document->getThumbnailKey()) {
            $document->setThumbnailPresignedUrl($this->s3Client->getPresignedUrl($document->getThumbnailKey()));
        }

        return $document;
    }

    public function hydrateDocumentWithPresignedUrl(Document $document): void
    {
        $document->setPresignedUrl($this->s3Client->getPresignedUrl($document->getObjectKey()));
    }

    private function createDocumentFromFile(
        File $file,
        string $key,
        string $fileName,
        Beneficiaire $beneficiary,
        ?Dossier $folder,
    ): Document {
        $user = $this->getUser();
        $document = (new Document())
            ->setExtension($file->guessExtension())
            ->setTaille($file->getSize())
            ->setObjectKey($key)
            ->setNom($fileName)
            ->setBeneficiaire($beneficiary)
            ->setDossier($folder)
            ->setBPrive($folder ? $folder->getBPrive() : $user?->isBeneficiaire());

        try {
            $thumbnailKey = $this->s3Client->generateThumbnail($file);
            $document
                ->setThumbnailKey($thumbnailKey)
                ->setThumbnailPresignedUrl($this->s3Client->getPresignedUrl($thumbnailKey));
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('There has been an error creating preview for file : %s', $exception->getMessage()));
        }

        $this->em->persist($document);
        $this->em->flush();

        return $document;
    }

    public function uploadFile(UploadedFile $file, Beneficiaire $beneficiary, Dossier $folder = null): ?Document
    {
        if ($this->isFileExtensionAllowed($file)) {
            try {
                $key = $this->s3Client->uploadFile($file);

                return $this->createDocumentFromFile($file, $key, $file->getClientOriginalName(), $beneficiary, $folder);
            } catch (\Exception $exception) {
                $this->logger->error(sprintf(
                    'There has been an error uploading file for beneficiary id = %d : %s',
                    $beneficiary->getId(),
                    $exception->getMessage()
                ));
                $this->addFlashMessage('danger', 'error');
            }
        }

        return null;
    }

    /**
     * @param UploadedFile[] $files
     *
     * @return Document[]
     */
    public function uploadFiles(array $files, Beneficiaire $beneficiary, Dossier $folder = null): array
    {
        return array_map(fn (UploadedFile $file) => $this->uploadFile($file, $beneficiary, $folder), $files);
    }

    private function isFileExtensionAllowed(UploadedFile $file): bool
    {
        $allowedFileExtensions = array_merge(
            Document::BROWSER_EXTENSIONS_VIEWABLE,
            Document::BROWSER_EXTENSIONS_NOT_VIEWABLE,
        );

        if (!in_array($file->guessExtension(), $allowedFileExtensions)) {
            $this->addFlashMessage(
                'danger',
                $this->translator->trans('unsupported_file_extension', ['%fileName%' => $file->getClientOriginalName()]),
            );

            return false;
        }

        return true;
    }

    public function downloadDocument(Document $document): ?StreamedResponse
    {
        $objectStream = $this->bucketService->getObjectStream($document->getObjectKey());
        $outputStream = fopen('php://output', 'wb');
        if (!$objectStream || !$outputStream) {
            return null;
        }

        $response = new StreamedResponse(fn () => stream_copy_to_stream(
            $objectStream,
            $outputStream,
        ));

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getNom(),
            u($document->getNom())->ascii()->toString(),
        ));

        return $response;
    }

    public function toggleVisibility(Document $document): void
    {
        $document->toggleVisibility();
        $this->em->flush();
    }

    public function delete(Document $document): void
    {
        try {
            if ($key = $document->getObjectKey()) {
                $this->bucketService->deleteFile($key);
            }
            if ($thumbKey = $document->getThumbnailKey()) {
                $this->bucketService->deleteFile($thumbKey);
            }
            $this->em->remove($document);
            $this->em->flush();
            $this->addFlashMessage('success', 'document_deleted_successfully');
        } catch (\Exception $e) {
            $this->addFlashMessage('danger', 'error');
        }
    }
}
