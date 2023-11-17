<?php

namespace App\ServiceV2\Mailer;

use App\Entity\User;
use Brevo\Client\Model\SendSmtpEmail;

class ShareDocumentEmail implements LocalizedTemplatedEmailInterface
{
    private const FR_TEMPLATE_ID = 9;
    private const EN_TEMPLATE_ID = 10;
    private const DE_TEMPLATE_ID = 11;
    private const ES_TEMPLATE_ID = 12;

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
                'BENEFICIARY_FIRST_NAME' => $sender->getPrenom(),
                'BENEFICIARY_LAST_NAME' => $sender->getNom(),
                'YEAR' => (new \DateTime())->format('Y'),
                'DOWNLOAD_URL' => $urlInMail,
            ],
        ]);
    }
}
