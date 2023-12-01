<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ShareDocumentLinkEmail
{
    private const TEMPLATE_PATH = 'v2/email/share_document_link.html.twig';
    private const SUBJECT = 'mail_subject_share_document';
    private const TRANSLATION_ROUTE = 'shared_document_mail_translation';

    public static function create(User $sender, string $recipientEmail, string $url): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($recipientEmail)
            ->subject(self::SUBJECT)
            ->htmlTemplate(self::TEMPLATE_PATH)
            ->context([
                'senderFullName' => $sender->getFullName(),
                'documentUrl' => $url,
                'year' => (new \DateTime())->format('Y'),
                'currentLocale' => User::DEFAULT_LANGUAGE,
                'translationRoute' => self::TRANSLATION_ROUTE,
            ]);
    }
}
