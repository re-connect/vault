<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Brevo\Client\Model\SendSmtpEmail;

interface LocalizedTemplatedEmailInterface
{
    public static function getTemplateIdFromLocale(string $locale): int;

    public static function create(string $recipientEmail, string $locale, string $urlInMail = null, User $sender = null): SendSmtpEmail;
}
