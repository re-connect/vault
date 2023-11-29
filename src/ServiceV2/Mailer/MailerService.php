<?php

namespace App\ServiceV2\Mailer;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private string $contactMail,
    ) {
    }

    public function send(Email $email): void
    {
        try {
            $this->mailer->send($email->sender($this->contactMail));
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
}
