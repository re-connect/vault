<?php

namespace App\Factory;

use App\Entity\Document;
use App\Entity\SharedDocument;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SharedDocumentFactory
{
    public const SELECTOR_LENGTH = 24;
    public const CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JWTTokenManagerInterface $JWTTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generateSharedDocument(User $user, Document $document, string $email, string $locale = 'FR'): SharedDocument
    {
        $expirationDate = new \DateTime('+7 days');
        $selector = $this->generateSelector();
        $token = $this->generateToken($user, $document->getId(), $expirationDate, $selector);
        $document->setPresignedUrl($this->getDownloadUrl($user->getId(), $token, $locale));

        $sharedDocument = (new SharedDocument())
            ->setSharedBy($user)
            ->setDocument($document)
            ->setSharedWithEmail($email)
            ->setExpirationDate($expirationDate)
            ->setToken($token)
            ->setSelector($selector);
        $this->em->persist($sharedDocument);
        $this->em->flush();

        return $sharedDocument;
    }

    private function generateSelector(): string
    {
        $string = '';
        for ($i = 0; $i < self::SELECTOR_LENGTH; ++$i) {
            try {
                $characterIndex = random_int(0, strlen(self::CHARACTERS) - 1);
            } catch (\Exception) {
                $characterIndex = 0;
            }
            $string .= self::CHARACTERS[$characterIndex];
        }

        return str_shuffle($string);
    }

    private function generateToken(User $user, int $documentId, \DateTime $expirationDate, string $selector): string
    {
        return $this->JWTTokenManager->createFromPayload($user, [
            'user_id' => $user->getId(),
            'document_id' => $documentId,
            'expiration_date' => $expirationDate,
            'selector' => $selector,
        ]);
    }

    public function getDownloadUrl(int $userId, string $token, string $locale): string
    {
        return $this->urlGenerator->generate('download_shared_document', [
            'id' => $userId,
            'token' => $token,
            'lang' => $locale,
        ], $this->urlGenerator::ABSOLUTE_URL);
    }
}
