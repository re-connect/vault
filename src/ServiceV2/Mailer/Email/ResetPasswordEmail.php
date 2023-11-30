<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ResetPasswordEmail
{
    private const TEMPLATE_PATH = 'v2/email/reset_password.html.twig';
    private const SUBJECT = 'mail_subject_reset_password';
    private const TRANSLATION_ROUTE = 'resetting_mail_translation';

    public static function create(User $user, string $url): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject(self::SUBJECT)
            ->htmlTemplate(self::TEMPLATE_PATH)
            ->context([
                'resetUrl' => $url,
                'year' => (new \DateTime())->format('Y'),
                'currentLocale' => $user->getLastLang(),
                'translationRoute' => self::TRANSLATION_ROUTE,
            ]);
    }
}
