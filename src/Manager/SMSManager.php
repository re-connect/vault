<?php

namespace App\Manager;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Evenement;
use App\Entity\User;
use App\Event\EvenementEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SMSManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $smsLogger,
        private readonly RouterInterface $router,
        private readonly TexterInterface $texter,
        private readonly RequestStack $requestStack,
        private readonly string $iOSAppLink,
        private readonly string $androidAppLink,
    ) {
    }

    /** @throws \Exception */
    public function sendAffiliationCodeSms(Beneficiaire $beneficiary): void
    {
        $telephone = $beneficiary->getUser()?->getTelephone();
        $code = $this->getRandomSmallSmsCode();
        $beneficiary->setRelayInvitationSmsCode($code);
        $message = $this->translator->trans('membre.sendSmsCode.smsMessage', ['%code%' => $code]);

        try {
            $this->em->flush();
            $this->doSendSms($telephone, $message);
        } catch (Exception) {
            $this->smsLogger->info(sprintf('Failure sending activation SMS to %s', $telephone));
        }
    }

    public function sendSmsActivation($subject): void
    {
        $telephone = $subject->getUser()->getTelephone();
        if (!$telephone) {
            return;
        }

        $activationSmsCodeLastSend = $subject->getActivationSmsCodeLastSend();
        if (null !== $activationSmsCodeLastSend && new \DateTime() <= $activationSmsCodeLastSend->add(new \DateInterval('PT1H'))) {
            return; // If code already sent and the one-hour timeout is still pending
        }

        $smsCode = $this->getRandomSmallSmsCode();
        $subject->setActivationSmsCode($smsCode)->setActivationSmsCodeLastSend(new \DateTime());

        $this->doSendSmsActivation($smsCode, $telephone);
        $this->em->persist($subject);
        $this->em->flush();
    }

    public function getRandomSmallSmsCode($length = 5)
    {
        $chars = '012345678901234567890123456789';

        return substr(str_shuffle($chars), 0, $length);
    }

    private function doSendSmsActivation($code, $number): void
    {
        $message = $this->translator->trans('membre.sendSmsCode.smsMessage', ['%code%' => $code]);

        try {
            $this->doSendSms($number, $message);
            $this->smsLogger->info('SMS envoyé à '.$number.' : '.$message);
        } catch (Exception) {
            $this->smsLogger->info(sprintf('Failure sending activation SMS to %s', $number));
        }
    }

    /**
     * @throws \Exception
     */
    public function doSendSms(string $number, string $message): void
    {
        $sms = (new SmsMessage($number, $message));
        if (str_contains($number, '+1')) {
            $sms->transport('vonageUS');
        }

        try {
            $this->texter->send($sms);
        } catch (\Exception $e) {
            $this->smsLogger->error(sprintf('%s. Cause: %s', $this->translator->trans('sms_sent_failed'), $e->getMessage()));
            $session = $this->requestStack->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->set('danger', 'sms_sent_failed');
            }

            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function sendSmsResetPassword($code, $number): void
    {
        $message = $this->translator->trans('user.reinitialiserMdp.smsMessage', ['%code%' => $code]);
        $this->doSendSms($number, $message);
        $this->smsLogger->info('SMS envoyé à '.$number.' : '.$message);
    }

    public function onEvenementEvent(EvenementEvent $event): void
    {
        echo PHP_EOL.'onEvenementEvent'.PHP_EOL;
        $rappel = $event->getRappel();
        $event = $rappel->getEvenement();
        $number = $event->getBeneficiaire()?->getUser()?->getTelephone();
        if (null === $number) {
            return;
        }

        try {
            $message = $this->getReminderMessage($event);
            $this->doSendSms($number, $message);
            $rappel->setBEnvoye(true);
            $this->em->flush();
            $this->smsLogger->info('SMS envoyé à '.$number.' : '.$message);
        } catch (\Exception $e) {
            $this->smsLogger->error(sprintf('Error sending sms to %s, content %s, cause %s ', $number, $message, $e->getMessage()));
        }
    }

    /**
     * Effacer tous les 'smsPasswordResetCode' qui sont encore présent après 24h
     * Cron à faire tourner dans everyMinuteCommand.
     *
     * @throws \Exception
     */
    public function nullifySmsPasswordResetCodesFromYesterday(): void
    {
        /** @var UserRepository $UserRepository */
        /** @var User $user */
        $em = $this->em;
        $UserRepository = $em->getRepository(User::class);
        $users = $UserRepository->FindBySmsPasswordResetCodeFromYesterday();

        // Passer à null tout les champs smsPasswordResetCode
        foreach ($users as $user) {
            $user->setSmsPasswordResetCode(null);
        }

        $em->flush();
    }

    /**
     * @throws \Exception
     */
    public function sendSMSBeneficiaryAddremotely(Beneficiaire $beneficiaire, $password): void
    {
        /** @var Centre $centreFirst */
        $centreFirst = $beneficiaire->getCentres()->first();
        $message = '';
        if (false !== $centreFirst) {
            $creatorUser = $beneficiaire->getCreatorUser();
            /** @var User $user */
            $user = $creatorUser->getEntity();
            $message .= $this->translator->trans('accompanying_person_propose_cfn_activation', [
                    '%person.firstname%' => $user->getPrenom(),
                    '%person.lastname%' => $user->getNom(),
                    '%center%' => $centreFirst->getNom(),
                ]).PHP_EOL;
        }
        $beneficiaireUser = $beneficiaire->getUser();
        $number = $beneficiaireUser->getTelephone();
        if (null === $number) {
            throw new \RuntimeException('Telephone number missing.');
        }

        $beneficiaireUser->setAutoLoginTokenDeliveredAt(new \DateTime());
        $autoLoginUrl = $this->router->generate('re_auto_login', [
            'token' => $beneficiaireUser->createAutoLoginToken()->toString(),
            'userId' => $beneficiaireUser->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $message .= $this->translator->trans('connect_reconnect_site').PHP_EOL;
        $message .= $this->translator->trans('username').' : '.$beneficiaireUser->getUsername().PHP_EOL;
        $message .= $this->translator->trans('password').' : '.$password.PHP_EOL;
        $message .= $this->translator->trans('or_click_link').' : '.$autoLoginUrl.PHP_EOL;
        $message .= $this->translator->trans('you_can_download_mobile_app').' : '.PHP_EOL;
        $message .= '- IOS (Apple) : '.$this->iOSAppLink.PHP_EOL;
        $message .= '- Android : '.$this->androidAppLink;

        $this->doSendSms($number, $message);
    }

    private function getReminderMessage(Evenement $event): string
    {
        $user = $event->getBeneficiaire()->getUser();

        $message = $this->translator->trans('reminder_sms_content', [
            '%name%' => $event->getNom(),
            '%date%' => $event->getDate()->format("d/m/Y à H\hi"), ], 'messages', $user?->getLastLang() ?? 'fr');

        if (!empty($event->getLieu())) {
            $message .= PHP_EOL.$event->getLieu();
        }
        if (!empty($event->getCommentaire())) {
            $message .= PHP_EOL.$event->getCommentaire();
        }

        return $message;
    }
}
