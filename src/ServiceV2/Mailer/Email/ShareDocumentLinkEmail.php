<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ShareDocumentLinkEmail implements LocalizedTemplatedEmailInterface
{
    private const TEMPLATE_PATH = 'v2/email/share_document_link.html.twig';
    private const SUBJECT = 'RECONNECT - Partage de document';
    private const TRANSLATION_ROUTE = 'shared_document_mail_translation';

    public static function create(string $locale, string $recipientEmail, string $urlInMail, User $sender = null): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($recipientEmail)
            ->subject(self::SUBJECT)
            ->htmlTemplate(self::TEMPLATE_PATH)
            ->context([
                'senderFullName' => $sender?->getFullName(),
                'documentUrl' => $urlInMail,
                'year' => (new \DateTime())->format('Y'),
                'currentLocale' => $locale,
                'translationRoute' => self::TRANSLATION_ROUTE,
            ]);
    }
}
