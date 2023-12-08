<?php

namespace App\Controller;

use App\Api\Manager\ApiClientManager;
use App\Entity\Document;
use App\Entity\User;
use App\ManagerV2\SharedDocumentManager;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SharedDocumentController extends REController
{
    private SharedDocumentManager $manager;

    public function __construct(
        SharedDocumentManager $manager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
        $this->manager = $manager;
    }

    #[Route(path: '/api/v2/documents/{id}/share', name: 'api_share_document', methods: ['POST'])]
    public function apiShareDocument(Request $request, AuthorizationCheckerInterface $authorizationChecker, Document $document): JsonResponse
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
                $this->manager->generateSharedDocumentAndSendEmail($document, $email, $request->getLocale());
            }
        }
        $jsonBody = [
            'status' => count($errors) > 0 ? 'Failure' : 'Ok',
            'errors' => $errors,
        ];

        return $this->json($jsonBody, $status);
    }
}
