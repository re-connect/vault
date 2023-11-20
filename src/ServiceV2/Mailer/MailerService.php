<?php

namespace App\ServiceV2\Mailer;

use App\Entity\SharedDocument;
use App\Entity\User;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class MailerService
{
    private TransactionalEmailsApi $transactionalEmailsApi;

    public function __construct(
        private Mailer $mailer,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private string $brevoApiKey,
        private string $noReplyMail,
        private array $adminMails,
    ) {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->brevoApiKey);
        $this->transactionalEmailsApi = new TransactionalEmailsApi(
            null,
            $config
        );
    }

    public function sendDocumentLink(SharedDocument $sharedDocument, string $email, string $locale = 'fr'): void
    {
        $document = $sharedDocument->getDocument();
        $user = $document->getBeneficiaire()?->getUser();

        $this->sendTemplatedEmail(ShareDocumentEmail::create($email, $locale, $document->getPresignedUrl(), $user));
    }

    public function sendResetPasswordLink(User $user, ResetPasswordToken $resetToken, string $locale = 'fr'): void
    {
        $url = $this->router->generate('app_reset_password_email', [
            'token' => $resetToken->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->sendTemplatedEmail(ResetPasswordEmail::create($user->getEmail(), $locale, $url));
    }

    public function sendDuplicatedUsernameAlert(User $user): void
    {
        if (!$user->hasSuffixedUsername() || !$user->isBeneficiaire()) {
            return;
        }

        $this->send(DuplicatedUsernameEmail::create($this->noReplyMail, $this->adminMails, $user));
    }

    public function sendTemplatedEmail(SendSmtpEmail $email): void
    {
        try {
            $this->transactionalEmailsApi->sendTransacEmail($email);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error sending templated email, cause : %s', $e->getMessage()));
        }
    }

    public function send(Email $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf('Error sending email %s, cause : %s', $email->getSubject(), $e->getMessage()));
        }
    }
}
