<?php

namespace App\Manager;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Provider\DocumentProvider;
use Mailjet\Client;
use Mailjet\Resources;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class MailManager
{
    private const SHARE_FILE_MAIL_TEMPLATE_ID = [
        'fr' => 3008320,
        'de' => 3616016,
        'en' => 3633387,
        'es' => 3633402,
    ];
    private Client $mailjet;
    private DocumentProvider $documentProvider;
    private Security $security;
    private DocumentManager $documentManager;

    public function __construct(
        $apikey,
        $apisecret,
        Security $security,
        DocumentProvider $documentProvider,
        DocumentManager $documentManager,
    ) {
        $this->mailjet = new Client($apikey, $apisecret, true, ['version' => 'v3.1']);
        $this->documentProvider = $documentProvider;
        $this->documentManager = $documentManager;
        $this->security = $security;
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
}
