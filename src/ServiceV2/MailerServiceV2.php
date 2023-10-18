<?php

namespace App\ServiceV2;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerServiceV2
{
    private Brevo
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function test(): void
    {
        $email = (new Email())
            ->from('contact@reconnect.fr')
            ->to('aresu.antoine@gmail.com')
            ->subject('[Coffre-Fort Numérique] Doublons bénéficiaire')
            ->text('test');
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            dd($e->getMessage());
        }
    }
}
