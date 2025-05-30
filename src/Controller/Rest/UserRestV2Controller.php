<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\User;
use App\Exception\JsonResponseException;
use App\Manager\RestManager;
use App\ManagerV2\UserManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\GestionnaireProvider;
use App\Provider\MembreProvider;
use App\Provider\UserProvider;
use App\ServiceV2\Helper\PasswordHelper;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['old' => '/api/', 'new' => '/api/v2/'], name: 're_api_user_')]
class UserRestV2Controller extends REController
{
    protected string $accessRead = Client::ACCESS_USER_READ;
    protected string $accessWrite = Client::ACCESS_USER_WRITE;
    protected string $accessDelete = Client::ACCESS_USER_DELETE;

    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        private readonly RestManager $restManager,
        private readonly UserProvider $provider,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    /**
     * @Get("user", name="get_mine")
     */
    #[Deprecated('This route belongs to the old API')]
    public function getMine(): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            return $this->json($user->jsonSerializeAPI());
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * Register device notification token.
     *
     * @Patch("user/register-notification-token", name="register_notification_token")
     */
    #[Deprecated('This route belongs to the old API')]
    public function setFcnToken(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            $user->setFcnToken($request->get('notification_token', null));
            $em->flush();

            return $this->json($user->jsonSerializeAPI());
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Rest\Put("users/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="edit"
     * )
     */
    public function edit(
        int $id,
        BeneficiaireProvider $beneficiaireProvider,
        MembreProvider $membreProvider,
        GestionnaireProvider $gestionnaireProvider
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            $entity = $this->provider->getEntity($id);
            $subject = $entity->getSubject();
            $request = $this->request;

            if (null === $request) {
                throw new BadRequestHttpException();
            }

            switch ($entity->getTypeUser()) {
                case User::USER_TYPE_BENEFICIAIRE:
                    $beneficiaireProvider->populate($subject);

                    if (null !== $data = $this->restManager->getJsonValidationError($subject, null, ['beneficiaire', 'beneficiaireQuestionSecrete', 'adresse'])) {
                        return $this->json($data, Response::HTTP_BAD_REQUEST);
                    }
                    $beneficiaireProvider->save($subject, $this->getUser());

                    break;
                case User::USER_TYPE_MEMBRE:
                    $membreProvider->populate($subject);

                    if (null !== $data = $this->restManager->getJsonValidationError($subject, null, ['membre', 'adresse'])) {
                        return $this->json($data, Response::HTTP_BAD_REQUEST);
                    }
                    $membreProvider->save($subject, $this->getUser());

                    break;
                case User::USER_TYPE_GESTIONNAIRE:
                    $gestionnaireProvider->populate($subject);

                    if (null !== $data = $this->restManager->getJsonValidationError($subject, null, ['gestionnaire'])) {
                        return $this->json($data, Response::HTTP_BAD_REQUEST);
                    }
                    $gestionnaireProvider->save($subject, $this->getUser());

                    break;
                default:
                    break;
            }

            $response = (null === $entity->getId()) ? Response::HTTP_CREATED : Response::HTTP_OK;

            return $this->json($entity->jsonSerializeAPI(), $response);
        } catch (AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    #[Route(path: 'user/password', name: 'update_my_password', methods: ['PATCH'])]
    public function updatePassword(Request $request, UserManager $userManager, PasswordHelper $helper): JsonResponse
    {
        $user = $this->getUser();
        $password = $request->request->get('password');
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        } elseif (!$password) {
            return $this->json(['password' => 'missing'], Response::HTTP_BAD_REQUEST);
        } elseif (!$helper->isStrongPassword($password)) {
            return $this->json(['password' => 'weak'], Response::HTTP_BAD_REQUEST);
        }

        $userManager->updatePassword($user, $password);

        return $this->json($user, 200, [], ['groups' => ['v3:user:read']]);
    }
}
