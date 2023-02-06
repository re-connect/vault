<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Manager\CentreManager;
use App\Manager\UserManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\CentreProvider;
use App\Provider\DocumentProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli/rosalie", name="re_api_api_rest_")
 */
class APIRestController extends REController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        ApiClientManager $clientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $clientManager);
    }

    /**
     * @Route(
     *     "/beneficiaire/{distantId}",
     *     methods={"GET"},
     *     name="get_beneficiaire"
     * )
     */
    public function getBeneficiaire(string $distantId, BeneficiaireProvider $beneficiaireProvider): JsonResponse
    {
        return $this->json($beneficiaireProvider->getEntityByDistantId($distantId, false)
            ->jsonSerializeForClient($this->apiClientManager->getCurrentOldClient()));
    }

    /**
     * @Route("/beneficiaire/{distantId}",
     *     name="edit_beneficiaire",
     *     methods={"PUT"}
     * )
     */
    public function editBeneficiaire(
        Request $request,
        string $distantId,
        BeneficiaireProvider $beneficiaireProvider
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId, false);
            $errorsArray = $beneficiaireProvider->populateBeneficiary($beneficiaire, $request->request, true);

            if (count($errorsArray) > 0) {
                return $this->json($errorsArray, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->entityManager->flush();

            return $this->json($beneficiaire->jsonSerializeForClient($this->apiClientManager->getCurrentOldClient()));
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route("/beneficiaire/{distantId}",
     *     name="delete_beneficiaire",
     *     methods={"DELETE"}
     * )
     */
    public function deleteBeneficiaire(
        string $distantId,
        BeneficiaireProvider $beneficiaireProvider,
        UserManager $userManager
    ): JsonResponse {
        try {
            $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId, false);
            $userManager->deleteUser($beneficiaire->getUser());

            return $this->json(['success' => 'Utilisateur supprimé'], Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route("/beneficiaire", name="create_beneficiaire", methods={"POST"})
     */
    public function createBeneficiaire(
        Request $request,
        UserManager $userManager,
        BeneficiaireProvider $beneficiaireProvider,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        try {
            if (null === $request->request->get('idRosalie') && null === $request->request->get('distant_id')) {
                throw new BadRequestHttpException('Missing distant id.');
            }

            $password = $userManager->randomPassword();
            $user = (new User())
                ->setBActif(true)
                ->setTypeUser(User::USER_TYPE_BENEFICIAIRE);

            $user->setPassword($hasher->hashPassword($user, $password));

            $beneficiaire = (new Beneficiaire())
                ->setUser($user)
                ->setIsCreating(false);

            $errorsArray = $beneficiaireProvider->populateBeneficiary($beneficiaire, $request->request, true);

            if (count($errorsArray) > 0) {
                return $this->json($errorsArray, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->entityManager->persist($beneficiaire);
            $this->entityManager->flush();

            return $this->json(array_merge(
                $beneficiaire->jsonSerializeForClient($this->apiClientManager->getCurrentOldClient()),
                ['password' => $password]
            ));
        } catch (BadRequestHttpException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route("/beneficiaire/{distantId}/uploadFile",
     *     requirements={"distantId": "\d{1,10}"},
     *     name="upload_file",
     *     methods={"POST"}
     * )
     */
    public function uploadFile(
        int $distantId,
        Request $request,
        BeneficiaireProvider $beneficiaireProvider,
        DocumentProvider $documentProvider
    ): JsonResponse {
        try {
            $client = $this->apiClientManager->getCurrentOldClient();
            $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId, false);

            if (!$uploadedFile = $request->files->get('file')) {
                return $this->json(['errors' => 'Aucun fichier reçu'], Response::HTTP_BAD_REQUEST);
            }

            $membreDistantId = $request->get('member_distant_id');
            $membre = $this->entityManager->getRepository(Membre::class)->findByDistantId($membreDistantId, $client->getRandomId());
            $byUser = $membre?->getUser() ?? $beneficiaire->getUser();

            try {
                $document = $documentProvider
                    ->uploadFile($uploadedFile, $beneficiaire, $client, $byUser)
                    ->setBPrive(false);
                $this->entityManager->flush();
            } catch (\Exception) {
                return $this->json(
                    ['errors' => 'Une erreur est intervenue lors de la tentative de dépôt du document,  Reconnect a été prévenu de la situation. Le compte sera vite pris en charge et le document déposé dessus.'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            $documentProvider->setDocumentsToClientFolder($document, $client);

            return $this->json(['success' => 'Le fichier a bien été uploadé']);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * Lier un beneficiaire distant à un centre distant.
     *
     * @Route("/beneficiaire/{distantBeneficiaryId}/centre/{distantCenterId}/link",
     *     requirements={
     *          "distantBeneficiaryId": "\d{1,10}",
     *          "distantCenterId": "\d{1,10}"
     *     },
     *     name="link_to_center",
     *     methods={"PATCH"}
     * )
     */
    public function linkToCenter(
        int $distantBeneficiaryId,
        int $distantCenterId,
        BeneficiaireProvider $beneficiaireProvider,
        CentreProvider $centreProvider,
        CentreManager $centreManager
    ): JsonResponse {
        try {
            $beneficiary = $beneficiaireProvider->getEntityByDistantId($distantBeneficiaryId, false);
            $center = $centreProvider->getEntityByDistantId($distantCenterId);

            $associateUserWithCentres = $centreManager->associateUserWithCentres($beneficiary, $center, null, null, true);
            if (!$associateUserWithCentres) {
                throw new NotAcceptableHttpException('Already associated.');
            }

            return $this->json($beneficiary->jsonSerializeForClient($this->apiClientManager->getCurrentOldClient()), Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException|AccessDeniedException|NotAcceptableHttpException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaire/{username}",
     *     requirements={
     *          "username": "[a-z\-]+\.[a-z\-]+\.[0-3][0-9]\/[0-1][0-9]\/[1-2][0-9]{3}(.[0-9]{1,2})?",
     *     },
     *     name="beneficiary_exists",
     *     methods={"GET"}
     * )
     */
    public function beneficiaryExists(string $username, BeneficiaireProvider $beneficiaryProvider): JsonResponse
    {
        try {
            $beneficiaryProvider->getEntityByUsername($username, null);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (NotFoundHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/centre/{distantId}",
     *     name="get_centre",
     *     methods={"GET"}
     * )
     */
    public function getCentre(string $distantId, CentreProvider $centreProvider): JsonResponse
    {
        try {
            return $this->json($centreProvider->getEntityByDistantId($distantId)
                ->jsonSerializeForClient($this->apiClientManager->getCurrentOldClient()));
        } catch (NotFoundHttpException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }
}
