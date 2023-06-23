<?php

namespace App\ManagerV2;

use App\Entity\Document;
use App\Entity\SharedDocument;
use App\Factory\SharedDocumentFactory;
use App\Repository\DocumentRepository;
use App\Repository\SharedDocumentRepository;
use App\Repository\UserRepository;
use App\ServiceV2\MailerService;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class SharedDocumentManager
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly SharedDocumentRepository $repository,
        private readonly SharedDocumentFactory $factory,
        private readonly MailerService $mailerService,
        private readonly UserRepository $userRepository,
        private readonly DocumentRepository $documentRepository,
        private RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function generateSharedDocumentAndSendEmail(Document $document, string $email, string $locale = 'fr'): void
    {
        $user = $this->getUser();
        if (!$user || !$document->getBeneficiaire()) {
            $this->addFlashMessage('danger', 'user_not_found');

            return;
        }
        $sharedDocument = $this->factory->generateSharedDocument($user, $document, $email, $locale);
        $this->mailerService->shareFileWithMail($sharedDocument, $email, $locale);

        $this->addFlashMessage('success', 'share_document_success');
    }

    public function validateTokenAndFetchDocument(string $token): ?SharedDocument
    {
        $decodedToken = $this->decodeSharedDocumentToken($token);
        list('selector' => $selector, 'user_id' => $userId, 'document_id' => $documentId) = $decodedToken;
        $sharedDocument = $this->repository->findOneBy(['selector' => $selector]);

        if ($errorMessage = $this->getErrorMessage($sharedDocument, (int) $userId, (int) $documentId)) {
            $this->addFlashMessage('danger', $errorMessage);
        }

        return $sharedDocument;
    }

    private function getErrorMessage(?SharedDocument $sharedDocument, int $userId, int $documentId): ?string
    {
        return match (true) {
            !$sharedDocument => 'share_document_error_no_shared_document',
            $sharedDocument->isExpired() => 'share_document_error_expired',
            !$this->userRepository->find($userId) || !$this->documentRepository->find($documentId) => 'share_document_error_not_found',
            default => null,
        };
    }

    /**
     * @return string[]
     */
    private function decodeSharedDocumentToken(string $token): array
    {
        return json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))), true);
    }
}
