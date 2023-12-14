<?php

namespace App\ServiceV2\Mailer\Email;

class ShareDocumentLinkEmail extends LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = 'v2/email/share_document_link.html.twig';
    protected const SUBJECT = 'mail_subject_share_document';
    protected const TRANSLATION_ROUTE = 'shared_document_mail_translation';
}
