<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\Client;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Membre;
use App\Entity\User;
use App\EventV2\BeneficiaryConsultationEvent;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Manager\RestManager;
use App\ManagerV2\SharedDocumentManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DocumentProvider;
use App\Provider\DossierProvider;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['old' => '/api/', 'new' => '/api/v2/'], name: 're_api_document_')]
class DocumentRestV2Controller extends REController
{
    public function __construct(
        private readonly DocumentProvider $provider,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    #[Route(path: 'beneficiaries/{beneficiaryId}/documents', name: 'list', requirements: ['beneficiaryId' => '\d{1,10}'], methods: ['GET'])]
    public function list(int $beneficiaryId, BeneficiaireProvider $beneficiaireProvider, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_READ);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;

            $dossierId = $this->request->query->get('folder');
            $dossier = null;

            if (null !== $dossierId) {
                /** @var Dossier $dossier */
                $dossier = $beneficiaire->getDossiers()->filter(static fn (Dossier $element) => $element->getId() === (int) $dossierId)->first();
                if (!$dossier) {
                    throw $this->createNotFoundException('No folder found for id '.$dossierId);
                }
                if ((!$isBeneficiaire && $dossier->getBPrive()) || (null === $this->getUser() && false !== $dossier->getBPrive())) {
                    throw $this->createAccessDeniedException();
                }
            } elseif ('root' === $this->request->query->get('q')) {
                $dossier = 'root';
            }

            $entities = $beneficiaire->getDocuments($isBeneficiaire, $dossier);

            foreach ($entities as $entity) {
                $this->provider->generatePresignedUris($entity);
            }

            // Record beneficiary consultation on this route, as it is the first reachable beneficiary page for mobile app
            $eventDispatcher->dispatch(new BeneficiaryConsultationEvent($beneficiaire));

            return $this->json($entities, 200, [], ['groups' => ['v3:document:read']]);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}', name: 'delete', requirements: ['id' => '\d{1,10}'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $document = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_DELETE);

            $this->provider->delete($document);

            return $this->json('', Response::HTTP_NO_CONTENT);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}', name: 'patch', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
    public function patch(int $id, DossierProvider $dossierProvider): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_WRITE);

            if (null !== $data = $this->request->request->all()) {
                foreach ($data as $key => $item) {
                    switch ($key) {
                        case 'folder_id':
                            if (null !== $item) {
                                $dossier = $dossierProvider->getEntity($item);
                                $dossierProvider->moveDocumentInside($entity, $dossier);
                            } else {
                                $this->provider->getOutFromFolder($entity);
                            }
                            break;
                        case 'nom':
                            $entity->setNom($item);
                            $this->provider->save($entity);
                            break;
                        case 'b_prive':
                            $bPrive = filter_var($item, FILTER_VALIDATE_BOOLEAN);
                            $this->provider->changePrive($entity, $bPrive);
                            break;
                        default:
                            break;
                    }
                }
            }

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}/{version}', name: 'show', requirements: ['id' => '\d{1,10}', 'version' => 'large|medium|originals|small|thumbnails|json'], defaults: ['version' => 'originals'], methods: ['GET'])]
    public function show(int $id, string $version = 'originals'): Response
    {
        try {
            $document = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_READ);

            /*
             * Nous n'avons pas besoin de vérifier si le doccument
             * est accessible car cette vérification se fait déjà lors de la récupération de l'entité juste au dessus.
             * nous pouvons donc mettre la variable $secured à 'false'.
             */
            if ('json' === $version) {
                return $this->json($document);
            } elseif ('originals' !== $version || in_array(strtolower($document->getExtension()), Document::BROWSER_EXTENSIONS_VIEWABLE)) {
                return $this->provider->show($document, $version, false);
            }

            return $this->redirectToRoute('re_app_document_telecharger', ['id' => $id]);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'beneficiaries/{beneficiaryId}/documents', name: 'upload', requirements: ['beneficiaryId' => '\d{1,10}'], methods: ['POST'])]
    public function upload(Request $request, int $beneficiaryId, BeneficiaireProvider $beneficiaireProvider, RestManager $restManager): Response
    {
        try {
            $client = $this->apiClientManager->getCurrentOldClient();
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_WRITE);

            $membreDistantId = $this->request->get('member_distant_id');
            $membre = $this->entityManager->getRepository(Membre::class)->findByDistantId($membreDistantId, $client->getRandomId());
            $byUser = $membre ? $membre->getUser() : $this->getUser();

            if (!$files = $request->files->get('files')) {
                return $this->json($restManager->getErrorsToJson(['files' => 'Missing files.']), Response::HTTP_BAD_REQUEST);
            }
            $entities = new ArrayCollection();
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $entities->add($this->provider->uploadFile($file, $beneficiaire, $client, $byUser));
            }
            foreach ($entities as $entity) {
                $this->provider->generatePresignedUris($entity);
            }

            return $this->json($entities->toArray());
        } catch (UploadException $e) {
            $jsonResponseException = new JsonResponseException($e, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);
        }

        return $jsonResponseException->getResponse();
    }

    #[Route(path: 'documents/{id}/send', name: 'send', requirements: ['id' => '\d{1,10}'], methods: ['POST'])]
    public function send(
        int $id,
        ValidatorInterface $validator,
        MailManager $mailManager,
        TranslatorInterface $translator,
        RestManager $restManager
    ): JsonResponse {
        try {
            $document = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_WRITE);

            if (null === $email = $this->request->get('email')) {
                return $this->json($restManager->getErrorsToJson(['email' => 'Missing email address.']), Response::HTTP_BAD_REQUEST);
            }

            $emailConstraint = new Assert\Email();

            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if (0 === count($errors)) {
                $mailManager->sendFileWithMail($document, $email);

                return $this->json($translator->trans('document_successfully_send_email'));
            }

            $errorMessage = $errors[0]->getMessage();

            throw new \RuntimeException($errorMessage);
        } catch (NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}/toggle-access', name: 'toggle_access', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
    public function toggleAccess(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_WRITE);

            $this->provider->changePrive($entity);
            $this->provider->generatePresignedUris($entity);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}', name: 'get', requirements: ['id' => '\d{1,10}'], methods: ['GET'])]
    public function getEntity(int $id): JsonResponse
    {
        try {
            $entity = $this->provider->getEntity($id, Client::ACCESS_DOCUMENT_READ);
            $this->provider->generatePresignedUris($entity);

            return $this->json($entity);
        } catch (NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}/name', name: 'rename', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
    public function renameAction(
        int $id,
        Request $request,
        DocumentProvider $provider,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            if (null === $name = $request->get('name')) {
                throw new BadRequestHttpException($this->translator->trans('donneePersonnelle.missingName'));
            }
            $entity = $provider->getEntity($id);

            $entity->setNom($name);
            $entityManager->flush();

            $provider->generateUrls($entity);

            return $this->json($entity);
        } catch (ValidatorException $e) {
            $jsonResponseException = new JsonResponseException($e, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);
        }

        return $jsonResponseException->getResponse();
    }

    #[Route(path: 'documents/{id}/folder/{dossierId}', name: 'put_in_folder', requirements: ['id' => '\d{1,10}', 'dossierId' => '\d{1,10}'], methods: ['PATCH'])]
    public function putInFolderAction(
        int $id,
        int $dossierId,
        DocumentProvider $provider,
        DossierProvider $dossierProvider
    ): JsonResponse {
        try {
            $entity = $provider->getEntity($id);

            $dossier = $dossierProvider->getEntity($dossierId);

            $dossierProvider->moveDocumentInside($entity, $dossier);

            $provider->generateUrls($entity);

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}/get-out-from-folder', name: 'get_out_from_folder', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
    public function getOutFromFolderAction(int $id, DocumentProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->getOutFromFolder($entity);
            $provider->generateUrls($entity);

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'documents/{id}/share', name: 'api_share_document', requirements: ['id' => '\d{1,10}'], methods: ['POST'])]
    public function shareDocument(Request $request, AuthorizationCheckerInterface $authorizationChecker, Document $document, SharedDocumentManager $manager): JsonResponse
    {
        $errors = [];
        $status = Response::HTTP_NO_CONTENT;
        if (false === $authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $document)) {
            $errors[] = $this->translator->trans('not_allowed_to_share_this_document');
            $status = Response::HTTP_FORBIDDEN;
        } else {
            $email = $request->request->get('email');
            $user = $this->getUser();
            if (!$email) {
                $errors[] = 'You must provide an email';
                $status = Response::HTTP_BAD_REQUEST;
            } elseif (!$user instanceof User) {
                $errors[] = 'User not found';
                $status = Response::HTTP_BAD_REQUEST;
            } else {
                $manager->generateSharedDocumentAndSendEmail($document, $email, $request->getLocale());
            }
        }
        $jsonBody = [
            'status' => count($errors) > 0 ? 'Failure' : 'Ok',
            'errors' => $errors,
        ];

        return $this->json($jsonBody, $status);
    }
}
