<?php

namespace App\ServiceV2\Mailer\Email;

class ResetPasswordEmail extends LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = 'v2/email/reset_password.html.twig';
    protected const SUBJECT = 'mail_subject_reset_password';
    protected const TRANSLATION_ROUTE = 'resetting_mail_translation';
}
