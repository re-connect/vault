<?php

namespace App\ServiceV2\Mailer;

use App\Entity\Centre;
use App\Entity\Region;
use App\Entity\SharedDocument;
use App\Entity\User;
use App\ServiceV2\Mailer\Email\AuthCodeEmail;
use App\ServiceV2\Mailer\Email\DuplicatedUsernameEmail;
use App\ServiceV2\Mailer\Email\ResetPasswordEmail;
use App\ServiceV2\Mailer\Email\ShareDocumentLinkEmail;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class MailerService implements AuthCodeMailerInterface
{
    use UserAwareTrait;

    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private Security $security,
        private string $mailerSender,
        private array $adminMails,
        private string $duplicateDefaultRecipient,
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
            [$user->getEmail()],
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
                [$email],
                User::DEFAULT_LANGUAGE,
                $document->getPresignedUrl(),
                $user,
            ));
        }
    }

    public function sendDuplicatedUsernameAlert(User $duplicatedUser): void
    {
        if (!$duplicatedUser->hasSuffixedUsername() || !$duplicatedUser->isBeneficiaire()) {
            return;
        }

        $recipients = new ArrayCollection();
        $pro = $this->getUser()?->getSubjectMembre();
        if ($pro) {
            $centres = $pro->getCentres();
            $regions = $centres->map(fn (Centre $centre) => $centre->getRegion());
            $recipients = $regions->map(fn (?Region $region) => $region?->getEmail())->filter(fn (?string $recipient) => null !== $recipient);
        }

        if ($recipients->isEmpty()) {
            $recipients->add($this->duplicateDefaultRecipient);
        }

        $this->send(DuplicatedUsernameEmail::create($recipients->toArray(), 'fr', '', null, ['duplicatedUser' => $duplicatedUser, 'userLang' => 'fr', 'client' => $duplicatedUser->getCreatorClient(), 'centres' => $centres ?? [], 'pro' => $pro]));
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
            [$user->getEmail() ?? ''],
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
