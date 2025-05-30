<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\User;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Manager\RestManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DossierProvider;
use App\Repository\FolderIconRepository;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['old' => '/api/', 'new' => '/api/v2/'], name: 're_api_dossier_')]
class DossierRestV2Controller extends REController
{
    #[Route(path: 'folders/{id}', name: 'delete', requirements: ['id' => '\d{1,10}'], methods: ['DELETE'])]
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

    #[Route(path: 'beneficiaries/{beneficiaryId}/folders', name: 'add', requirements: ['beneficiaryId' => '\d{1,10}'], methods: ['POST'])]
    public function add(
        int $beneficiaryId,
        DossierProvider $provider,
        BeneficiaireProvider $beneficiaireProvider,
        FolderIconRepository $folderIconRepository,
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
                        case 'icon_id':
                            $entity->setIcon($item ? $folderIconRepository->find($item) : null);
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

    #[Route(path: 'folders/{id}', name: 'patch', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
    public function patch(int $id, DossierProvider $provider, FolderIconRepository $folderIconRepository): JsonResponse
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
                        case 'icon_id':
                            $entity->setIcon($item ? $folderIconRepository->find($item) : null);
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

    #[Route(path: 'beneficiaries/{beneficiaryId}/folders', name: 'list', requirements: ['beneficiaryId' => '\d{1,10}'], methods: ['GET'])]
    public function list(int $beneficiaryId, BeneficiaireProvider $beneficiaireProvider, AuthorizationCheckerInterface $authorizationChecker): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId, Client::ACCESS_DOCUMENT_READ);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
                throw $this->createAccessDeniedException("Vous n'avez pas le droit d'accéder aux dossiers de ce bénéficiaire.");
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

    #[Route(path: 'folders/{id}/send', name: 'send', requirements: ['id' => '\d{1,10}'], methods: ['POST'])]
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
                return $this->json($restManager->getErrorsToJson(['folder' => $translator->trans('empty')]), Response::HTTP_BAD_REQUEST);
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

                return $this->json('', Response::HTTP_NO_CONTENT);
            }

            // this is *not* a valid email address
            $errorMessage = $errors[0]->getMessage();

            throw new BadRequestHttpException($errorMessage);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException|\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'folders/{id}/toggle-access', name: 'toggle_access', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
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

    #[Route(path: 'folders/{id}/name', name: 'rename', requirements: ['id' => '\d{1,10}'], methods: ['PATCH'])]
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
