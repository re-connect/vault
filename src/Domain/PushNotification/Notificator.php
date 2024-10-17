<?php

namespace App\Domain\PushNotification;

use App\Checker\FeatureFlagChecker;
use App\Domain\PushNotification\Notification\DocumentAddedPushNotification;
use App\Domain\PushNotification\Notification\PushNotificationInterface;
use App\Entity\Document;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class Notificator
{
    private const string EXPO_BASE_URL = 'https://exp.host/--/api/v2/push';
    private const string EXPO_SEND_URL = self::EXPO_BASE_URL.'/send';

    public function __construct(
        private HttpClientInterface $httpClient,
        private TranslatorInterface $translator,
        private FeatureFlagChecker $featureFlagChecker,
        private LoggerInterface $logger,
    ) {
    }

    public function sendDocumentAddedNotification(Document $document): void
    {
        $this->sendNotification(new DocumentAddedPushNotification($document));
    }

    private function sendNotification(PushNotificationInterface $notification): void
    {
        if (!$this->featureFlagChecker->isEnabled('create_document_push_notification')) {
            return;
        }
        if (!$notification->canBeSent()) {
            return;
        }
        try {
            $this->httpClient->request(
                Request::METHOD_POST,
                self::EXPO_SEND_URL,
                ['body' => $this->buildBody($notification)],
            );
        } catch (ExceptionInterface $e) {
            $this->logger->error(sprintf('Error sending push notification, cause: %s', $e->getMessage()));
        }
    }

    private function buildBody(PushNotificationInterface $notification): array
    {
        return [
            'to' => $notification->getRecipient(),
            'sound' => 'default',
            'title' => $this->translator->trans($notification->getTitle()),
            'body' => $this->translator->trans($notification->getMessage()),
        ];
    }
}
