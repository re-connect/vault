<?php

namespace App\Domain\PushNotification\Notification;

use App\Entity\Attributes\Document;

readonly class DocumentAddedPushNotification implements PushNotificationInterface
{
    private ?string $recipient;
    private string $title;
    private string $message;

    public function __construct(Document $document)
    {
        $this->recipient = $document->getBeneficiaire()->getUser()->getFcnToken();
        $this->title = 'document_added';
        $this->message = 'document_added_to_vault';
    }

    #[\Override]
    public function getTitle(): string
    {
        return $this->title;
    }

    #[\Override]
    public function getMessage(): string
    {
        return $this->message;
    }

    #[\Override]
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    #[\Override]
    public function canBeSent(): bool
    {
        return null !== $this->recipient;
    }
}
