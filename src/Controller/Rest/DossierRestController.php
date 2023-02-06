<?php

namespace App\Controller\Rest;

use App\Entity\Dossier;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\MailManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DossierProvider;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli", name="api_folder_", options={"expose"=true})
 */
class DossierRestController extends AbstractController
{
    /**
     * @Route(
     *     "/folders/{id}/toggle-access",
     *     name="toggle_access",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function toggleAccessAction(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->changePrive($entity);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function deleteAction(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->delete($entity);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{beneficiaryId}/folders",
     *     name="add",
     *     methods={"PATCH", "POST"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     }
     * )
     */
    public function addAction(int $beneficiaryId, Request $request, DossierProvider $provider, BeneficiaireProvider $beneficiaireProvider): JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId);

            $entity = $provider->createFolder($beneficiaire);

            if ($request->isMethod(Request::METHOD_POST)) {
                $provider->setNom($entity, $request);
            }

            return $this->json($entity, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}/name",
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

    /**
     * @Route(
     *     "/beneficiaries/{beneficiaryId}/folders",
     *     name="list",
     *     methods={"GET"},
     *     requirements={
     *          "beneficiaryId": "\d{1,10}"
     *     }
     * )
     */
    public function listAction(
        int $beneficiaryId,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($beneficiaryId);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'accéder aux dossiers de ce bénéficiaire.");
            }

            $user = $this->getUser();
            $isBeneficiaire = $user instanceof User ? $user->isBeneficiaire() : false;

            $criteria['beneficiaire'] = $beneficiaire;
            if (!$isBeneficiaire) {
                $criteria['bPrive'] = false;
            }

            $dossiers = $entityManager->getRepository(Dossier::class)->findBy($criteria, ['nom' => Criteria::ASC]);

            return $this->json($dossiers);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}/send",
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
        DossierProvider $provider,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        MailManager $mailManager
    ): JsonResponse {
        try {
            $dossier = $provider->getEntity($id);
            if ($dossier->getDocuments()->isEmpty()) {
                throw new \RuntimeException($translator->trans('folder.envoyerParEmail.dossierVide'));
            }

            $email = $request->request->get('email');

            if (null === $email) {
                throw new MissingMandatoryParametersException('Missing email address.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $emailConstraint = new Assert\Email();

            // use the validator to validate the value
            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if ($request->isMethod(Request::METHOD_POST)) {
                if (0 === count($errors)) {
                    $mailManager->sendFolderWithMail($dossier, $email);

                    return $this->json($translator->trans('folder.envoyerParEmail.bienEnvoyeParMail'), Response::HTTP_ACCEPTED);
                }

                // this is *not* a valid email address
                $errorMessage = $errors[0]->getMessage();

                // ... do something with the error
                throw new \RuntimeException($errorMessage);
            }
            throw new MethodNotAllowedHttpException([Request::METHOD_POST]);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}/report-abuse",
     *     name="report_abuse",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function reportAbuseAction(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->reportAbuse($entity);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}",
     *     name="get_entity",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function getEntityAction(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}/folder/{dossierId}",
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
        DossierProvider $provider
    ): JsonResponse {
        try {
            $entity = $provider->getEntity($id);

            $dossier = $provider->getEntity($dossierId);

            $provider->moveDossierInside($entity, $dossier);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/folders/{id}/get-out-from-folder",
     *     name="get_out_from_folder",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}",
     *     }
     * )
     */
    public function getOutFromFolderAction(int $id, DossierProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $provider->getOutFromFolder($entity);

            return $this->json($entity);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
