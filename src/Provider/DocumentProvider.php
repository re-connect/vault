<?php

namespace App\Provider;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Entity\User;
use App\Event\DonneePersonnelleEvent;
use App\Event\REEvent;
use App\Form\Type\DocumentSimpleType;
use App\Form\Type\DocumentType;
use App\Manager\DocumentManager;
use App\Repository\ClientRepository as OldClientRepository;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use App\Service\PdfService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\ExtensionFileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DocumentProvider extends DonneePersonnelleProvider
{
    protected DocumentManager $manager;
    protected string $entityName = Document::class;
    protected string $formType = DocumentType::class;
    protected string $formSimpleType = DocumentSimpleType::class;
    private RouterInterface $router;
    protected PdfService $pdfService;

    public function __construct(
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        DocumentManager $manager,
        RouterInterface $router,
        PdfService $pdfService,
        OldClientRepository $oldClientRepository,
        ClientRepository $clientRepository,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct(
            $formFactory,
            $authorizationChecker,
            $em,
            $tokenStorage,
            $eventDispatcher,
            $validator,
            $translator,
            $requestStack,
            $oldClientRepository,
            $clientRepository,
            $apiClientManager,
        );
        $this->manager = $manager;
        $this->router = $router;
        $this->pdfService = $pdfService;
    }

    public function findOneBy($criteria)
    {
        return $this->em->getRepository(Document::class)->findOneBy($criteria);
    }

    public function getLastUploadedDoc(Beneficiaire $beneficiaire)
    {
        $result = $this->em->createQueryBuilder()
            ->select('d')
            ->from('App:Document', 'd')
            ->innerJoin('d.beneficiaire', 'b')
            ->where('b.id = '.$beneficiaire->getId())
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        if (null === $result || 0 === count($result)) {
            return null;
        }

        return $result[0];
    }

    public function maxSizeSoonReached(Beneficiaire $beneficiaire): bool
    {
        return $beneficiaire->getTotalFileSize() / $this->getMaxSizeForBeneficiaire() > 0.9;
    }

    public function getMaxSizeForBeneficiaire()
    {
        return 1024 * 1024 * 300;
    }

    public function getEntity(int $id, ?string $accessAttribute = null): Document
    {
        if (!$entity = $this->em->getRepository(Document::class)->find($id)) {
            throw new NotFoundHttpException('No document found for id '.$id);
        }

        return $entity;
    }

    public function getEntities(Beneficiaire $beneficiaire, ?int $dossierId = null): array
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $isBeneficiaire = $user->isBeneficiaire();

        $dossiers = $this->em->getRepository(Dossier::class)
            ->findAllowed($beneficiaire, $isBeneficiaire, $dossierId);
        $documents = $this->em->getRepository(Document::class)
            ->findAllowed($beneficiaire, $isBeneficiaire, $dossierId);

        $entities = array_merge($dossiers, $documents);

        foreach ($entities as $entity) {
            if ($entity instanceof Document) {
                $this->generateUrls($entity);
            } else {
                /** @var Dossier $entity */
                foreach ($entity->getDocuments() as $value) {
                    $this->generateUrls($value);
                }
            }
        }

        return $entities;
    }

    public function generateUrls(Document $document)
    {
        $id = $document->getId();
        $document->setThumb($this->router->generate('api_document_show', ['id' => $id, 'version' => 'small']));
        $document->setUrl($this->router->generate('api_document_show', ['id' => $id]));

        $this->generateUrlsAction($document);
    }

    private function generateUrlsAction(Document $document)
    {
        $id = $document->getId();
        $document->setDeleteUrl($this->router->generate('api_document_delete', ['id' => $id]));
        $document->setRenameUrl($this->router->generate('api_document_rename', ['id' => $id]));
        $document->setToggleAccessUrl($this->router->generate('api_document_toggle_access', ['id' => $id]));
    }

    public function generatePresignedUris(Document $document): void
    {
        if (null !== $key = $document->getObjectKey()) {
            $document->setUrl($this->manager->getPresignedUrl($key));
        }
        if (null !== $thumbnailKey = $document->getThumbnailKey()) {
            $document->setThumb($this->manager->getPresignedUrl($thumbnailKey));
        }
        $this->generateUrlsAction($document);
    }

    public function hydrateDocumentWithUris(Document $document): void
    {
        $this->generatePresignedUris($document);
    }

    /**
     * @param Document[]|Collection
     */
    public function hydrateDocumentsWithUris($documents): void
    {
        foreach ($documents as $document) {
            $this->hydrateDocumentWithUris($document);
        }
    }

    public function getEntitiesFromBeneficiaire(Beneficiaire $beneficiaire): array
    {
        if (false === $this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
            throw new AccessDeniedException('donneePersonnelle.cantDisplay');
        }

        $qb = $this->em->createQueryBuilder()
            ->select('d', 'do')
            ->from($this->entityName, 'd')
            ->innerJoin('d.beneficiaire', 'b')
            ->leftJoin('d.dossier', 'do')
            ->where('b.id = '.$beneficiaire->getId())
            ->orderBy('d.id', 'DESC');

        $user = $this->tokenStorage->getToken()->getUser();

        if ($this->authorizationChecker->isGranted(User::USER_TYPE_MEMBRE, $user)) {
            $qb->andWhere('d.bPrive = false');
            $qb->andWhere('do.id IS NULL OR do.bPrive = false');
        }

        return $qb
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * Création d'un zip des documents pour le téléchargement d'un dossier ou l'envoi par mail.
     *
     * @param array|ArrayCollection|Document[] $documents
     *
     * @return bool|false|string
     *
     * @throws \Exception
     */
    public function createZipFromDocuments($documents = [], bool $asBase64 = true)
    {
        if (0 === $documents->count()) {
            throw new \RuntimeException('erreur : le dossier est vide.');
        }

        $destination = tempnam('/tmp/', 'sendzip_'.$documents[0]->getBeneficiaire()->getId().'_');
        if (file_exists($destination)) {
            unlink($destination);
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($destination.'.zip', \ZipArchive::CREATE)) {
            return false;
        }

        $unlinkDocuments = [];
        foreach ($documents as $document) {
            $key = $document->getObjectKey();
            $url = $this->manager->getPresignedUrl($key);
            $content = file_get_contents($url);
            $fileName = $document->getNom();
            $i = 0;
            $pathTmp = '/tmp/'.$document->getBeneficiaire()->getId().'_';
            while (file_exists($pathTmp.$fileName)) {
                ++$i;
                $fileName = $document->getNameWithoutExtension()." ($i).".$document->getExtension();
            }

            $pathFile = $pathTmp.$fileName;

            $handle = fopen($pathFile, 'w');
            fwrite($handle, $content);
            fclose($handle);
            $zip->addFile($pathFile, $fileName);
            $unlinkDocuments[] = $pathFile;
        }
        $zip->close();
        foreach ($unlinkDocuments as $unlinkDocument) {
            unlink($unlinkDocument);
        }

        $b64data = file_get_contents($destination.'.zip');
        if ($asBase64) {
            $b64data = base64_encode($b64data);
        }
        unlink($destination.'.zip');

        return $b64data;
    }

    public function createZipFromFolder(Dossier $folder): ?StreamedResponse
    {
        $documents = $folder->getDocuments();
        if (0 === $documents->count()) {
            return null;
        }

        foreach ($documents as $document) {
            if (!$this->manager->getObjectStream($document->getObjectKey())) {
                return null;
            }
        }

        return new StreamedResponse(function () use ($folder, $documents) {
            $options = new Archive();
            $options->setZeroHeader(true);
            $zip = new ZipStream($folder->getNom(), $options);

            foreach ($documents as $document) {
                $zip->addFileFromStream(
                    $document->getNom(),
                    $this->manager->getObjectStream($document->getObjectKey())
                );
            }
            $zip->finish();
        });
    }

    public function delete(DonneePersonnelle $donneePersonnelle, bool $log = true): void
    {
        if ($donneePersonnelle instanceof Document) {
            parent::delete($donneePersonnelle, $log);
            $this->deleteOnBucket($donneePersonnelle);
        }
    }

    private function deleteOnBucket(Document $document): void
    {
        if ($key = $document->getObjectKey()) {
            $this->deleteKeyOnBucket($key);
        }
        if ($thumbKey = $document->getThumbnailKey()) {
            $this->deleteKeyOnBucket($thumbKey);
        }
    }

    private function deleteKeyOnBucket(string $key): void
    {
        $this->manager->deleteFile($key);
    }

    public function getOutFromFolder(Document $document): Document
    {
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $document)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $document->setDossier();
        $this->em->persist($document);
        $this->em->flush();

        $this->eventDispatcher->dispatch(
            new DonneePersonnelleEvent(
                $document,
                $document->getBeneficiaire()->getUser(),
                DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED
            ),
            REEvent::RE_EVENT_DONNEEPERSONNELLE,
        );

        return $document;
    }

    public function setDocumentsToClientFolder(Document $entity, Client $client)
    {
        if (null !== $dossierNom = $client->getDossierNom()) {
            $beneficiaire = $entity->getBeneficiaire();
            $dossier = $beneficiaire->getDossiers()->filter(static function (Dossier $dossier) use ($dossierNom) {
                return $dossier->getNom() === $dossierNom;
            })->first();

            if (!$dossier) {
                $dossier = new Dossier();
                $dossier
                    ->setBeneficiaire($beneficiaire)
                    ->setNom($dossierNom);
                $this->em->persist($dossier);
            }

            $entity->setDossier($dossier);

            $this->em->persist($entity);
            $this->em->flush();
        }
    }

    public function show(Document $document, string $version = 'originals', $secured = true): Response
    {
        if ($secured && false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $document)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de voir ce document.");
        }

        $key = $document->getObjectKey();

        if ('originals' !== $version) {
            $package = new Package(new JsonManifestVersionStrategy(__DIR__.'/../../public/build/manifest.json'));

            if (in_array(strtolower($document->getExtension()), ['doc', 'docx', 'txt', 'odt'])) {
                $filepath = $package->getUrl('build/images/icons/word.png');
            } elseif (in_array(strtolower($document->getExtension()), ['xls', 'xlsx', 'csv'])) {
                $filepath = $package->getUrl('build/images/icons/excel.PNG');
            } elseif (null !== $thumbKey = $document->getThumbnailKey()) {
                $filepath = $this->manager->getPresignedUrl($thumbKey);

                return new RedirectResponse($filepath);
            }

            if (!empty($filepath)) {
                $response = new BinaryFileResponse(__DIR__.'/../../public'.$filepath);

                return $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    $document->getNom()
                );
            }
        }

        $filepath = $this->manager->getPresignedUrl($key);

        return new RedirectResponse($filepath);
    }

    public function uploadFile(
        UploadedFile $file,
        Beneficiaire $beneficiaire,
        Client $client = null,
        ?UserInterface $byUser = null,
        ?Dossier $dossier = null,
        bool $log = true
    ): Document {
        try {
            if (false === $this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
                throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
            }

            $uploadExtensionsAllow = array_merge(Document::BROWSER_EXTENSIONS_VIEWABLE, Document::BROWSER_EXTENSIONS_NOT_VIEWABLE);

            if (!in_array($file->guessExtension(), $uploadExtensionsAllow)) {
                throw new ExtensionFileException('Extension not allowed '.$file->guessExtension());
            }

            $extension = str_replace('jpeg', 'jpg', $file->guessExtension());

            $document = new Document();

            if ($byUser instanceof User) {
                if ($byUser->isBeneficiaire()) {
                    $document->setBPrive(true);
                } else {
                    $creatorUser = (new CreatorUser())->setEntity($byUser);
                    $document->addCreator($creatorUser);
                }
            }

            if (null !== $client) {
                $creatorClient = (new CreatorClient())->setEntity($client);
                $document->addCreator($creatorClient);
            }

            $key = $this->manager->putFile($file);
            $thumbnailKey = null;
            $fileIsImage = getimagesize($file->getPathname());

            if ('application/pdf' === $file->getMimeType()) {
                try {
                    $originalFilename = $file instanceof UploadedFile ? $file->getClientOriginalName() : $originalFilename = $file->getFilename();
                    $thumbnailName = 'thumbnail-'.$originalFilename;
                    $thumbnailPath = $this->pdfService->genPdfThumbnail($file->getPathname(), $thumbnailName.'.jpeg');
                    $thumbnailFile = new File($thumbnailPath);
                    $thumbnailKey = $this->manager->putFile($thumbnailFile);
                    unlink($thumbnailFile);
                } catch (\Exception) {
                }
            } elseif (false !== $fileIsImage) {
                $thumbnailName = '/tmp/thumbnail-'.$file->getClientOriginalName();
                $im = new \Imagick();
                $im->readImage($file->getRealPath());
                $im->thumbnailImage(500, 500, true);
                $im->writeImage($thumbnailName);
                $thumbnailFile = new File($thumbnailName);
                $thumbnailKey = $this->manager->putFile($thumbnailFile);
                unlink($thumbnailFile);
            }

            $document
                ->setExtension($extension)
                ->setTaille($file->getSize())
                ->setObjectKey($key)
                ->setThumbnailKey($thumbnailKey)
                ->setNom($file->getClientOriginalName())
                ->setBeneficiaire($beneficiaire);

            if ($byUser instanceof User && $byUser->isBeneficiaire()) {
                $document->setBPrive(true);
            }

            if (null !== $dossier) {
                $document
                    ->setBPrive($dossier->getBPrive())
                    ->setDossier($dossier);
            }

            $totalFileSize = $beneficiaire->getTotalFileSize() + $file->getSize();
            $beneficiaire->setTotalFileSize($totalFileSize);

            if ($log) {
                $this->eventDispatcher->dispatch(
                    new DonneePersonnelleEvent($document, $byUser, DonneePersonnelleEvent::DONNEEPERSONNELLE_CREATED),
                    REEvent::RE_EVENT_DONNEEPERSONNELLE,
                );
            }

            $this->em->persist($document);
            $this->em->persist($beneficiaire);
            $this->em->flush();

            return $document;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function rename(DonneePersonnelle $donneePersonnelle, $newNameWithOrWithoutExtension, bool $andPersist = true)
    {
        /** @var Document $donneePersonnelle */
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $sanitizedString = $this->sanitize($newNameWithOrWithoutExtension);
        $sanitizedString = preg_replace('#.'.$donneePersonnelle->getExtension().'$#', '', $sanitizedString);

        $newNameWithoutExtension = $sanitizedString;

        $donneePersonnelle->setNom($newNameWithoutExtension.'.'.$donneePersonnelle->getExtension());

        if ($andPersist) {
            $this->em->persist($donneePersonnelle);
            $this->em->flush();
        }

        return $sanitizedString;
    }
}
