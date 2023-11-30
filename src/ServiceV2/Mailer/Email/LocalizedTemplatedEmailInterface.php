<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

interface LocalizedTemplatedEmailInterface
{
    public static function create(string $locale, string $recipientEmail, string $urlInMail, User $sender = null): TemplatedEmail;
}
