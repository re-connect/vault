<?php

namespace App\Manager;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Provider\DocumentProvider;
use Mailjet\Client;
use Mailjet\Resources;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class MailManager
{
    private const SHARE_FILE_MAIL_TEMPLATE_ID = [
        'fr' => 3008320,
        'de' => 3616016,
        'en' => 3633387,
        'es' => 3633402,
    ];
    private const RESET_PASSWORD_MAIL_TEMPLATE_ID = [
        'fr' => 1553981,
        'de' => 3616070,
        'en' => 3633288,
        'es' => 3633322,
    ];
    private Client $mailjet;
    private DocumentProvider $documentProvider;
    private Security $security;
    private DocumentManager $documentManager;
    private RouterInterface $router;
    private TranslatorInterface $translator;

    public function __construct(
        $apikey,
        $apisecret,
        Security $security,
        DocumentProvider $documentProvider,
        DocumentManager $documentManager,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->mailjet = new Client($apikey, $apisecret, true, ['version' => 'v3.1']);
        $this->documentProvider = $documentProvider;
        $this->documentManager = $documentManager;
        $this->security = $security;
        $this->router = $router;
        $this->translator = $translator;
    }

    private function sendTemplate($id, $dest, $variables = [], $attachment = null)
    {
        $body = [
            'Messages' => [
                [
                    'To' => [
                        [
                            'Email' => $dest,
                            'Name' => 'passenger 1',
                        ],
                    ],
                    'TemplateID' => $id,
                    'TemplateLanguage' => true,
                    'Variables' => $variables,
                ],
            ],
        ];
        if ($attachment) {
            $body['Messages'][0]['Attachments'] = [$attachment];
        }

        return $this->mailjet->post(Resources::$Email, ['body' => $body]);
    }

    public function sendFileWithMail(Document $document, $dest)
    {
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $email = $user->getEmail() ?: '';
            $prenom = $user->getPrenom();
            $nom = $user->getNom();
        } else {
            $email = '';
            $prenom = '';
            $nom = '';
        }
        $variables = [
            'RE_NOM' => $nom,
            'RE_PRENOM' => $prenom,
            'RE_USERNAME' => $email,
            'SUBJECT' => '',
            'MC_PREVIEW_TEXT' => '',
            'RE_LIEN_PANNEAU_UTILISATEUR' => '',
            'YEAR' => (new \DateTime())->format('Y'),
        ];
        $filename = $document->getNom();
        $key = $document->getObjectKey();
        $url = $this->documentManager->getPresignedUrl($key);
        $b64dataContent = base64_encode(file_get_contents($url));
        $extension = strtolower($document->getExtension());
        if ('pdf' === $extension) {
            $contentType = 'application/pdf';
        } elseif ('png' === $extension || 'jpg' === $extension || 'gif' === $extension) {
            $contentType = 'image/*';
        } else {
            $contentType = 'text/*';
        }
        $attachment = [
            'ContentType' => $contentType,
            'Filename' => $filename,
            'Base64Content' => $b64dataContent,
        ];
        $this->sendTemplate(1552248, $dest, $variables, $attachment);
    }

    /**
     * @throws \Exception
     */
    public function sendFolderWithMail(Dossier $dossier, $dest)
    {
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $email = $user->getEmail() ?: '';
            $prenom = $user->getPrenom();
            $nom = $user->getNom();
        } else {
            $email = '';
            $prenom = '';
            $nom = '';
        }
        $variables = [
            'RE_NOM' => $nom,
            'RE_PRENOM' => $prenom,
            'RE_USERNAME' => $email,
            'SUBJECT' => '',
            'MC_PREVIEW_TEXT' => '',
            'RE_LIEN_PANNEAU_UTILISATEUR' => '',
            'YEAR' => (new \DateTime())->format('Y'),
        ];
        /**
         * Attachment.
         */
        $b64dataContent = $this->documentProvider->createZipFromDocuments($dossier->getDocuments());
        $attachment = [
            'ContentType' => 'application/octet-stream',
            'Filename' => $dossier->getNom().'.zip',
            'Base64Content' => $b64dataContent,
        ];
        $this->sendTemplate(1552248, $dest, $variables, $attachment);
    }

    public function sendResettingEmailMessageV2(UserInterface $user, ResetPasswordToken $resetToken, string $locale = 'fr'): void
    {
        $templateId = self::RESET_PASSWORD_MAIL_TEMPLATE_ID[$locale] ?? self::RESET_PASSWORD_MAIL_TEMPLATE_ID['fr'];
        $url = $this->router->generate('app_reset_password_email', [
            'token' => $resetToken->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $variables = [
            'SUBJECT_RESET_PASSWORD' => $this->translator->trans('mail_subject_reset_password'),
            'MC_PREVIEW_TEXT' => '',
            'RE_URL_RESET' => $url,
        ];
        $this->sendTemplate($templateId, $user->getEmail(), $variables);
    }
}
