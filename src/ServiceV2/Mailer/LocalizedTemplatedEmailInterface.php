<?php

namespace App\ServiceV2\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

interface LocalizedTemplatedEmailInterface
{
    public static function create(string $locale, string $recipientEmail, string $urlInMail): TemplatedEmail;
}
