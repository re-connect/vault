<?php

namespace App\ServiceV2\Mailer;

use App\Entity\SharedDocument;
use App\Entity\User;
use App\ServiceV2\Mailer\Email\AuthCodeEmail;
use App\ServiceV2\Mailer\Email\DuplicatedUsernameEmail;
use App\ServiceV2\Mailer\Email\ResetPasswordEmail;
use App\ServiceV2\Mailer\Email\ShareDocumentLinkEmail;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class MailerService implements AuthCodeMailerInterface
{
    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly string $mailerSender,
        private readonly array $adminMails,
    ) {
    }

    public function send(Email $email): void
    {
        $email->sender($email->getSender() ?? $this->mailerSender);

        if ($email instanceof TemplatedEmail) {
            $userLang = $email->getContext()['userLang'];
            $email->subject($this->translator->trans($email->getSubject(), [], 'messages', $userLang ?? User::DEFAULT_LANGUAGE));
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf('Error sending mail, cause : %s', $e->getMessage()));
        }
    }

    public function sendResetPasswordLink(User $user, ResetPasswordToken $token): void
    {
        $userLastLang = $user->getLastLang();

        $url = $this->router->generate('app_reset_password_email', [
            'token' => $token->getToken(),
            'lang' => $userLastLang,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->send(ResetPasswordEmail::create(
            $user->getEmail(),
            $userLastLang,
            $url,
        ));
    }

    public function sendSharedDocumentLink(SharedDocument $sharedDocument, string $email): void
    {
        $document = $sharedDocument->getDocument();
        $user = $document?->getBeneficiaire()?->getUser();

        if ($document && $user) {
            $this->send(ShareDocumentLinkEmail::create(
                $email,
                User::DEFAULT_LANGUAGE,
                $document->getPresignedUrl(),
                $user,
            ));
        }
    }

    public function sendDuplicatedUsernameAlert(User $user): void
    {
        if (!$user->hasSuffixedUsername() || !$user->isBeneficiaire()) {
            return;
        }

        $this->send(DuplicatedUsernameEmail::create($this->mailerSender, $this->adminMails, $user));
    }

    public function sendPersonalDataRequestEmail(User $user): void
    {
        $email = (new Email())
            ->subject('CFN - Demande de récupération de données')
            ->text(sprintf(
                'Un utilisateur (id user = %d) vient d’effectuer une demande de récupération de ses données sur le coffre-fort numérique (%s)',
                $user->getId(),
                (new \DateTime())->format('d/m/Y à H\hi'),
            ))
            ->to(...$this->adminMails);

        $this->send($email);
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        /** @var User $user */
        $authCode = $user->getEmailAuthCode();
        $this->send(email: AuthCodeEmail::create(
            $user->getEmail() ?? '',
            $user->getLastLang(),
            '',
            null,
            [
                'authCode' => $authCode,
                'username' => $user->getFullName(),
            ]
        ));
    }
}
