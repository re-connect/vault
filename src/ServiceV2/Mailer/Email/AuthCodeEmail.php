<?php

namespace App\ServiceV2\Mailer\Email;

class AuthCodeEmail extends LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = 'v2/email/auth_code.html.twig';
    protected const SUBJECT = 'mail_auth_code_subject';
    protected const TRANSLATION_ROUTE = null;
}
