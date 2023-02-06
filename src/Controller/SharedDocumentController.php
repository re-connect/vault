<?php

namespace App\Controller;

use App\Api\Manager\ApiClientManager;
use App\Entity\Document;
use App\Entity\User;
use App\Form\Type\EmailType;
use App\Manager\DocumentManager;
use App\ManagerV2\SharedDocumentManager;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use App\Service\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    /**
     * @Route(
     *     "/api/v2/documents/{id}/share",
     *     name="api_share_document",
     *     methods={"POST"})
     */
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

    /**
     * @Route(
     *     "/appli/document/{id}/share",
     *     name="share_document",
     *     methods={"GET", "POST"})
     */
    public function shareDocument(Request $request, AuthorizationCheckerInterface $authorizationChecker, Document $document): Response
    {
        if (false === $authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $document)) {
            throw new AccessDeniedException($this->translator->trans('not_allowed_to_share_this_document'));
        }
        $shareForm = $this->createForm(EmailType::class, null, [
            'action' => $this->generateUrl('share_document', ['id' => $document->getId()]),
        ])->handleRequest($request);
        if ($shareForm->isSubmitted() && $shareForm->isValid()) {
            $this->manager->generateSharedDocumentAndSendEmail($document, $shareForm->get('email')->getData(), $request->getLocale());

            return $this->redirectToRoute('re_app_document_list', ['id' => $document->getBeneficiaire()->getId()]);
        }

        return $this->render('app\document\share.html.twig', [
            'shareForm' => $shareForm->createView(),
            'document' => $document,
        ]);
    }

    /**
     * @Route("/public/download-document/{token}", name="public_download_share_document", methods={"GET"})
     */
    public function publicDownloadSharedDocument(
        Request $request,
        LanguageService $languageService,
        TranslatorInterface $translator,
        DocumentManager $documentManager,
        string $token
    ): Response {
        if ($lang = $request->query->get('lang')) {
            $languageService->setLocaleInSession($lang);
            if ($request->getLocale() !== $translator->getLocale()) {
                return $this->redirectToRoute(
                    'public_download_share_document',
                    [
                        'lang' => $request->getLocale(),
                        'token' => $token,
                    ]
                );
            }
        }
        if (!$sharedDocument = $this->manager->validateTokenAndFetchDocument($token)) {
            return $this->redirectToRoute('home');
        }

        return $this->render('download\download.html.twig', [
            'sharedDocument' => $sharedDocument,
            'downloadLink' => $documentManager->getPresignedUrl($sharedDocument->getDocumentKey()),
        ]);
    }
}
