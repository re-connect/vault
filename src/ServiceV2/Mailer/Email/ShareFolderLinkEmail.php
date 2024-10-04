<?php

namespace App\ServiceV2\Mailer\Email;

class ShareFolderLinkEmail extends LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = 'v2/email/share_folder_link.html.twig';
    protected const SUBJECT = 'mail_subject_share_folder';
    protected const TRANSLATION_ROUTE = 'shared_document_mail_translation';
}
