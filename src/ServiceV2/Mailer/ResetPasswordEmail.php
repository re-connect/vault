<?php

namespace App\ServiceV2\Mailer;

use App\Entity\User;
use Brevo\Client\Model\SendSmtpEmail;

readonly class ResetPasswordEmail implements LocalizedTemplatedEmailInterface
{
    private const FR_TEMPLATE_ID = 5;
    private const EN_TEMPLATE_ID = 6;
    private const DE_TEMPLATE_ID = 7;
    private const ES_TEMPLATE_ID = 8;

    public static function getTemplateIdFromLocale(string $locale): int
    {
        return match ($locale) {
            'en' => self::EN_TEMPLATE_ID,
            'de' => self::DE_TEMPLATE_ID,
            'es' => self::ES_TEMPLATE_ID,
            default => self::FR_TEMPLATE_ID,
        };
    }

    public static function create(string $recipientEmail, string $locale, string $urlInMail = null, User $sender = null): SendSmtpEmail
    {
        return new SendSmtpEmail([
            'to' => [['email' => $recipientEmail]],
            'templateId' => self::getTemplateIdFromLocale($locale),
            'params' => [
                'YEAR' => (new \DateTime())->format('Y'),
                'RESET_URL' => $urlInMail,
            ],
        ]);
    }
}
