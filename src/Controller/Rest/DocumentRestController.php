<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\Document;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DocumentProvider;
use App\Provider\DossierProvider;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli", name="api_document_", options={"expose"=true})
 */
class DocumentRestController extends REController
{
    /**
     * @Route(
     *     "/beneficiaries/{beneficiaryId}/documents",
     *     name="list",
     *     methods={"GET"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}",
     *     }
     * )
     */
    public function listAction(
        int $beneficiaryId,
        DocumentProvider $provider,
        BeneficiaireProvider $beneficiaireProvider
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId);

            $entities = $provider->getEntities($beneficiaire);

            return $this->json($entities);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/documents/{id}/toggle-access",
     *     name="toggle_access",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function toggleAccessAction(int $id, DocumentProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->changePrive($entity);
            $provider->generateUrls($entity);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{beneficiaryId}/folders/{folderId}/documents",
     *     name="list_from_folder",
     *     methods={"GET"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}",
     *     }
     * )
     */
    public function listFromFolderAction(
        int $beneficiaryId,
        int $folderId,
        DocumentProvider $provider,
        BeneficiaireProvider $beneficiaireProvider
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId);

            if (-1 === $folderId) {
                $folderId = null;
            }
            $entities = $provider->getEntities($beneficiaire, $folderId);

            return $this->json($entities);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/documents/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function deleteAction(
        int $id,
        DocumentProvider $provider,
        AuthorizationCheckerInterface $authorizationChecker
    ): JsonResponse {
        try {
            $document = $provider->getEntity($id);

            if (false === $authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_DELETE, $document)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de supprimer ce fichier.");
            }

            $provider->delete($document);

            return $this->json('', Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Move the file to the source folder.
     *
     * @Route(
     *     "/documents/{id}/get-out-from-folder",
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
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Report abuse Route annotation.
     *
     * @Route(
     *     "/documents/{id}/report-abuse",
     *     name="report_abuse",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function reportAbuseAction(int $id, DocumentProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->reportAbuse($entity);
            $provider->generateUrls($entity);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/documents/{id}/name",
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
     * Move the file to the folder.
     *
     * @Route(
     *     "/documents/{id}/folder/{dossierId}",
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
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/documents/{id}/{version}",
     *     defaults={"version": "originals"},
     *     name="show",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}",
     *          "version" : "large|medium|originals|small|thumbnails"
     *     }
     * )
     */
    public function showAction(int $id, DocumentProvider $provider, ?string $version = 'originals'): Response
    {
        try {
            $document = $provider->getEntity($id);

            // Nous n'avons pas besoin de vérifier si le doccument est accessible car cette vérification se fait déjà lors de la récupération de l'entité juste au dessus
            // nous pouvons donc mettre la variable $secured à 'false'.
            if ('originals' !== $version || in_array(strtolower($document->getExtension()), Document::BROWSER_EXTENSIONS_VIEWABLE)) {
                return $provider->show($document, $version, false);
            }

            return $this->redirectToRoute('re_app_document_telecharger', ['id' => $id]);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Download the file.
     *
     * @Route(
     *     "/beneficiaries/{beneficiaryId}/documents",
     *     name="upload",
     *     methods={"POST"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     }
     * )
     */
    public function uploadAction(
        int $beneficiaryId,
        DocumentProvider $provider,
        BeneficiaireProvider $beneficiaireProvider,
        DossierProvider $dossierProvider,
        Request $request
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId);

            $dossier = null;
            if (null !== ($folderId = $request->get('folder')) && -1 !== (int) $folderId && 'undefined' !== $folderId) {
                $dossier = $dossierProvider->getEntity($folderId);
            }

            if (null === $files = $request->files->get('files')) {
                throw new BadRequestHttpException($this->translator->trans('files.missing'));
            }

            $user = $this->getUser();
            $byUser = $user ?? $beneficiaire->getUser();

            $documents = new ArrayCollection();
            foreach ($files as $file) {
                $documents->add($provider->uploadFile($file, $beneficiaire, null, $byUser, $dossier));
            }
            foreach ($documents as $document) {
                $provider->generateUrls($document);
            }

            $response['files'] = $documents->toArray();

            return $this->json($response, Response::HTTP_CREATED);
        } catch (UploadException $e) {
            $jsonResponseException = new JsonResponseException($e, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);
        }

        return $jsonResponseException->getResponse();
    }

    /**
     * Send the file by email.
     *
     * @Route(
     *     "/documents/{id}/send",
     *     name="send",
     *     methods={"POST"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function sendAction(
        int $id,
        Request $request,
        DocumentProvider $provider,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        MailManager $mailManager
    ): JsonResponse {
        try {
            $document = $provider->getEntity($id);

            if (null === $email = $request->request->get('email')) {
                throw new MissingMandatoryParametersException('Missing email address.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $emailConstraint = new Assert\Email();

            // use the validator to validate the value
            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if (0 === count($errors)) {
                $mailManager->sendFileWithMail($document, $email);

                return $this->json($translator->trans('document.envoyerParEmail.bienEnvoyeParMail'));
            }

            // this is *not* a valid email address
            $errorMessage = $errors[0]->getMessage();

            // ... do something with the error
            throw new \RuntimeException($errorMessage);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
