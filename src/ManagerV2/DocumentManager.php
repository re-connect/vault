<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentManager
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly FlysystemS3Client $s3Client,
        private readonly DocumentRepository $repository,
        private readonly DossierRepository $folderRepository,
        private readonly EntityManagerInterface $em,
        private Security $security,
        private readonly LoggerInterface $logger,
        private RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly BucketService $bucketService,
    ) {
    }

    /**
     * @return Document[]
     */
    public function getAllDocumentsWithUrl(Beneficiaire $beneficiary, ?Dossier $folder = null): array
    {
        return $this->getDocumentsWithUrl($this->repository->findAllByBeneficiary($beneficiary, $folder));
    }

    /**
     * @return Document[]
     */
    public function getSharedDocumentsWithUrl(Beneficiaire $beneficiary, ?Dossier $folder = null): array
    {
        return $this->getDocumentsWithUrl($this->repository->findSharedByBeneficiary($beneficiary, $folder));
    }

    /**
     * @param Document[] $documents
     *
     * @return Document[]
     */
    public function getDocumentsWithUrl(array $documents): array
    {
        foreach ($documents as $document) {
            $this->getDocumentWithUrl($document);
        }

        return $documents;
    }

    public function getDocumentWithUrl(Document $document): Document
    {
        $document->setPresignedUrl($this->s3Client->getPresignedUrl($document->getObjectKey()));
        if ($document->getThumbnailKey()) {
            $document->setThumbnailPresignedUrl($this->s3Client->getPresignedUrl($document->getThumbnailKey()));
        }

        return $document;
    }

    /**
     * @return array<Document|Dossier>
     */
    public function getAllFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, ?Dossier $parentFolder = null): array
    {
        return [
            ...$this->folderRepository->findAllByBeneficiary($beneficiary, $parentFolder),
            ...$this->getAllDocumentsWithUrl($beneficiary, $parentFolder),
        ];
    }

    /**
     * @return array<Document|Dossier>
     */
    public function getSharedFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, ?Dossier $parentFolder = null): array
    {
        return [
            ...$this->folderRepository->findSharedByBeneficiary($beneficiary, $parentFolder),
            ...$this->getSharedDocumentsWithUrl($beneficiary, $parentFolder),
        ];
    }

    /**
     * @return array<Document|Dossier>
     */
    public function searchFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $word
            ? [...$this->folderRepository->searchByBeneficiary($beneficiary, $word, $folder), ...$this->searchDocumentsWithUrl($beneficiary, $word, $folder)]
            : $this->getAllFoldersAndDocumentsWithUrl($beneficiary, $folder);
    }

    /**
     * @return array<Document|Dossier>
     */
    public function searchSharedFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $word
            ? [...$this->folderRepository->searchSharedByBeneficiary($beneficiary, $word, $folder), ...$this->searchSharedDocumentsWithUrl($beneficiary, $word, $folder)]
            : $this->getSharedFoldersAndDocumentsWithUrl($beneficiary, $folder);
    }

    /**
     * @return Document[]
     */
    public function searchDocumentsWithUrl(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $this->getDocumentsWithUrl($this->repository->searchByBeneficiary($beneficiary, $word, $folder));
    }

    /**
     * @return Document[]
     */
    public function searchSharedDocumentsWithUrl(Beneficiaire $beneficiary, ?string $word, Dossier $folder = null): array
    {
        return $this->getDocumentsWithUrl($this->repository->searchSharedByBeneficiary($beneficiary, $word, $folder));
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
            ->setBPrive($user?->isBeneficiaire() || $folder?->getBPrive());

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

    public function uploadFile(UploadedFile $file, Beneficiaire $beneficiary, ?Dossier $folder = null): ?Document
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
    public function uploadFiles(array $files, Beneficiaire $beneficiary, ?Dossier $folder = null): array
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
        if (!$this->bucketService->getObjectStream($document->getObjectKey())) {
            return null;
        }

        $response = new StreamedResponse(function () use ($document) {
            stream_copy_to_stream(
                $this->bucketService->getObjectStream($document->getObjectKey()),
                fopen('php://output', 'wb')
            );
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $document->getNom()
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
            $this->addFlashMessage('success', 'document.bienSupprime');
        } catch (\Exception $e) {
            $this->addFlashMessage('danger', 'error');
        }
    }

    public function move(Document $document, ?Dossier $folder): void
    {
        if (!$folder) {
            $document->setDossier();
        } else {
            $folder->addDocument($document);
            $document->setBPrive($folder->getBPrive());
        }

        $this->em->flush();
    }
}
