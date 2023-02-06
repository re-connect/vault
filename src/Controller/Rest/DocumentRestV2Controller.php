<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\Client;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Membre;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DocumentProvider;
use App\Provider\DossierProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   }, name="re_api_document_")
 */
class DocumentRestV2Controller extends REController
{
    private DocumentProvider $provider;

    public function __construct(
        DocumentProvider $provider,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApiClientManager $apiClientManager,
    ) {
        $this->provider = $provider;
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    /**
     * @Route(
     *     "beneficiaries/{beneficiaryId}/documents",
     *     methods={"GET"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     },
     *     name="list"
     * )
     */
    public function list(int $beneficiaryId, BeneficiaireProvider $beneficiaireProvider): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_READ);
            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;

            $dossierId = $this->request->query->get('folder');
            $dossier = null;
            if (null !== $dossierId) {
                /** @var Dossier $dossier */
                $dossier = $beneficiaire->getDossiers()->filter(static function (Dossier $element) use ($dossierId) {
                    return $element->getId() === (int) $dossierId;
                })->first();
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

            return $this->json($entities);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "documents/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}",
     *     name="patch",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}/{version}",
     *     methods={"GET"},
     *     name="show",
     *     defaults={"version": "originals"},
     *     requirements={
     *          "id": "\d{1,10}",
     *          "version" : "large|medium|originals|small|thumbnails|json"
     *     }
     * )
     */
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

    /**
     * @Route("beneficiaries/{beneficiaryId}/documents",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     },
     *     name="upload",
     *     methods={"POST"}
     * )
     */
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

    /**
     * @Route("documents/{id}/send",
     *     methods={"POST"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="send"
     * )
     */
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

                return $this->json($translator->trans('document.envoyerParEmail.bienEnvoyeParMail'));
            }

            $errorMessage = $errors[0]->getMessage();

            throw new \RuntimeException($errorMessage);
        } catch (NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "documents/{id}/toggle-access",
     *     name="toggle_access",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}",
     *     name="get",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}/name",
     *     name="rename",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}/folder/{dossierId}",
     *     name="put_in_folder",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *          "dossierId": "\d{1,10}"
     *     }
     * )
     */
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

    /**
     * @Route(
     *     "documents/{id}/get-out-from-folder",
     *     name="get_out_from_folder",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
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
}
