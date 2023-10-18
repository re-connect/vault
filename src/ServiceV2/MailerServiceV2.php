<?php

namespace App\ServiceV2;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerServiceV2
{
    private TransactionalEmailsApi $transactionalEmailsApi;
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', 'myLittleSecretKey');
        $this->transactionalEmailsApi = new TransactionalEmailsApi(
            null,
            $config
        );
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

    public function testApi(): void
    {
        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => 'this is a test',
            'sender' => ['name' => 'antoine', 'email' => 'contact@reconnect.fr'],
            'to' => [['name' => 'antoine', 'email' => 'aresu.antoine@gmail.com']],
            'templateId' => 1,
            'params' => ['param' => 'hola'],
        ]);

        try {
            $this->transactionalEmailsApi->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
