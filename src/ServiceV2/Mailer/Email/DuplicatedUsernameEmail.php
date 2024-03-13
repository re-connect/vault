<?php

namespace App\ServiceV2\Mailer\Email;

class DuplicatedUsernameEmail extends LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = 'v2/email/duplicated_user.html.twig';
    protected const SUBJECT = 'duplicate_mail_subject';
    protected const TRANSLATION_ROUTE = null;
}
