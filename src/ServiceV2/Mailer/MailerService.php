<?php

namespace App\ServiceV2\Mailer;

use App\Entity\SharedDocument;
use App\Entity\User;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class MailerService
{
    private TransactionalEmailsApi $transactionalEmailsApi;

    public function __construct(
        private string $brevoApiKey,
        private RouterInterface $router,
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

    public function sendTemplatedEmail(SendSmtpEmail $email): void
    {
        try {
            $this->transactionalEmailsApi->sendTransacEmail($email);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
