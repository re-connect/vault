<?php

namespace App\ServiceV2\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ResetPasswordEmail implements LocalizedTemplatedEmailInterface
{
    private const TEMPLATE_PATH = 'v2/email/reset_password.html.twig';
    private const SUBJECT = 'RECONNECT - Réinitialiser un mot de passe';
    private const TRANSLATION_ROUTE = 'resetting_mail_translation';

    public static function create(string $locale, string $recipientEmail, string $urlInMail): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($recipientEmail)
            ->subject(self::SUBJECT)
            ->htmlTemplate(self::TEMPLATE_PATH)
            ->context([
                'resetUrl' => $urlInMail,
                'year' => (new \DateTime())->format('Y'),
                'currentLocale' => $locale,
                'translationRoute' => self::TRANSLATION_ROUTE,
            ]);
    }
}
