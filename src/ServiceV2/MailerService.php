<?php

namespace App\ServiceV2;

use App\Entity\SharedDocument;
use App\Entity\User;
use Mailjet\Client;
use Mailjet\Resources;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class MailerService
{
    private Client $mailjet;
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

    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
        string $apikey,
        string $apisecret,
        private readonly string $noReplyMail,
        private readonly array $adminMails,
    ) {
        $this->mailjet = new Client($apikey, $apisecret, true, ['version' => 'v3.1']);
    }

    public function shareFileWithMail(SharedDocument $sharedDocument, string $email, string $locale): void
    {
        $document = $sharedDocument->getDocument();
        $user = $document->getBeneficiaire()?->getUser();
        $templateId = self::SHARE_FILE_MAIL_TEMPLATE_ID[$locale] ?? self::SHARE_FILE_MAIL_TEMPLATE_ID['fr'];
        $variables = [
            'RE_NOM' => $user->getNom(),
            'RE_PRENOM' => $user->getPrenom(),
            'SUBJECT_SHARE_DOC' => $this->translator->trans('mail_subject_share_document'),
            'LINK' => $document->getPresignedUrl(),
            'YEAR' => (new \DateTime())->format('Y'),
        ];
        $this->sendTemplate($templateId, $email, $variables);
    }

    /**
     * @param array<string, ?string> $variables
     */
    private function sendTemplate(int $id, string $dest, array $variables = []): void
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

        $this->mailjet->post(Resources::$Email, ['body' => $body]);
    }

    public function sendDuplicateUsernameAlert(User $user): void
    {
        if (!$user->hasSuffixedUsername() || !$user->isBeneficiaire()) {
            return;
        }

        $email = (new Email())
            ->from($this->noReplyMail)
            ->to(...$this->adminMails)
            ->subject('[Coffre-Fort Numérique] Doublons bénéficiaire')
            ->text(sprintf('Doublons de username bénéficiaire : %s', $user->getUsername()));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf(
                'Error sending duplicate username alert, cause : %s',
                $e->getMessage(),
            ));
        }
    }

    public function sendResettingEmailMessage(User $user, ResetPasswordToken $resetToken, string $locale = 'fr'): void
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
