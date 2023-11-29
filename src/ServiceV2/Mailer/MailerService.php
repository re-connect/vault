<?php

namespace App\ServiceV2\Mailer;

use App\Entity\SharedDocument;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class MailerService
{
    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly string $contactMail,
        private readonly string $noReplyMail,
        private readonly array $adminMails,
    ) {
    }

    public function send(Email $email): void
    {
        try {
            $this->mailer->send($email->sender($email->getSender() ?? $this->contactMail));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf('Error sending mail, cause : %s', $e->getMessage()));
        }
    }

    public function sendResetPasswordLink(User $user, ResetPasswordToken $token, string $locale): void
    {
        $url = $this->router->generate('app_reset_password_email', [
            'token' => $token->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->send(ResetPasswordEmail::create($locale, $user->getEmail(), $url));
    }

    public function sendSharedDocumentLink(SharedDocument $sharedDocument, string $email, string $locale): void
    {
        $document = $sharedDocument->getDocument();

        $this->send(ShareDocumentLinkEmail::create(
            $locale,
            $email,
            $document?->getPresignedUrl(),
            $document?->getBeneficiaire()?->getUser(),
        ));
    }

    public function sendDuplicatedUsernameAlert(User $user): void
    {
        if (!$user->hasSuffixedUsername() || !$user->isBeneficiaire()) {
            return;
        }

        $this->send(DuplicatedUsernameEmail::create($this->noReplyMail, $this->adminMails, $user));
    }
}
