<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\Client;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DossierProvider;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   }, name="re_api_dossier_")
 */
class DossierRestV2Controller extends REController
{
    /**
     * @Route(
     *     "folders/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function delete(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id, Client::ACCESS_DOCUMENT_DELETE);

            $provider->delete($entity);

            return $this->json('', Response::HTTP_NO_CONTENT);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{beneficiaryId}/folders",
     *     methods={"POST"},
     *     name="add",
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     }
     * )
     */
    public function add(
        int $beneficiaryId,
        DossierProvider $provider,
        BeneficiaireProvider $beneficiaireProvider
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_WRITE);
            $entity = $provider->create($beneficiaire);
            $user = $this->getUser();
            if ($user instanceof User && $user->isBeneficiaire()) {
                $entity->setBPrive(true);
            }

            $request = $this->request;
            if (null !== $data = $request->request->all()) {
                foreach ($data as $key => $item) {
                    switch ($key) {
                        case 'nom':
                            $entity->setNom($item);
                            break;
                        case 'b_prive':
                            if (!array_key_exists('dossier_parent_id', $data)) {
                                $bPrive = filter_var($item, FILTER_VALIDATE_BOOLEAN);
                                $entity->setBPrive($bPrive);
                            }
                            break;
                        case 'dossier_parent_id':
                            $dossierParent = $provider->getEntity($item);
                            if ($beneficiaire->getId() === $dossierParent->getBeneficiaire()->getId()) {
                                $entity
                                    ->setDossierParent($dossierParent)
                                    ->setBPrive($dossierParent->getBPrive());
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            $provider->addCreatorClient($entity);
            $provider->save($entity);

            return $this->json($entity, Response::HTTP_CREATED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "folders/{id}",
     *     name="patch",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function patch(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id, Client::ACCESS_DOCUMENT_WRITE);

            $request = $this->request;
            if (null !== $data = $request->request->all()) {
                foreach ($data as $key => $item) {
                    switch ($key) {
                        case 'nom':
                            $entity->setNom($item);
                            $this->entityManager->flush();
                            break;
                        case 'b_prive':
                            $bPrive = filter_var($item, FILTER_VALIDATE_BOOLEAN);
                            $provider->changePrive($entity, $bPrive);
                            break;
                        case 'dossier_parent_id':
                            if (null === $item) {
                                $provider->getOutFromFolder($entity);
                            } else {
                                $dossierParent = $provider->getEntity($item);
                                $provider->moveDossierInside($entity, $dossierParent);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            $user = $this->getUser();
            if ($entity->getBPrive() && ($user instanceof User && !$user->isBeneficiaire() && !$user->isAdministrateur())) {
                $entity = null;
            }

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{beneficiaryId}/folders",
     *     name="list",
     *     methods={"GET"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     }
     * )
     */
    public function list(int $beneficiaryId, BeneficiaireProvider $beneficiaireProvider, AuthorizationCheckerInterface $authorizationChecker): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_READ);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'accéder aux dossiers de ce bénéficiaire.");
            }

            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;
            $dossiers = $beneficiaire->getDossiers($isBeneficiaire);

            return $this->json($dossiers);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "folders/{id}/send",
     *     name="send",
     *     methods={"POST"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function send(
        int $id,
        Request $request,
        DossierProvider $provider,
        MailManager $mailManager,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        RestManager $restManager
    ): JsonResponse {
        try {
            $dossier = $provider->getEntity($id, Client::ACCESS_DOCUMENT_READ);
            if ($dossier->getDocuments()->isEmpty()) {
                return $this->json($restManager->getErrorsToJson(['folder' => $translator->trans('folder.envoyerParEmail.dossierVide')]), Response::HTTP_BAD_REQUEST);
            }

            if (null === $email = $request->request->get('email')) {
                return $this->json($restManager->getErrorsToJson(['email' => 'Missing email address.']), Response::HTTP_BAD_REQUEST);
            }

            $emailConstraint = new Assert\Email();

            // use the validator to validate the value
            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if (0 === count($errors)) {
                $mailManager->sendFolderWithMail($dossier, $email);

                return $this->json('', RESPONSE::HTTP_NO_CONTENT);
            }

            // this is *not* a valid email address
            $errorMessage = $errors[0]->getMessage();

            throw new BadRequestHttpException($errorMessage);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "folders/{id}/toggle-access",
     *     name="toggle_access",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function toggleAccess(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->changePrive($entity);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "folders/{id}/name",
     *     name="rename",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function renameAction(int $id, Request $request, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->setNom($entity, $request);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (ValidatorException $e) {
            $jsonResponseException = new JsonResponseException($e, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);
        }

        return $jsonResponseException->getResponse();
    }
}
