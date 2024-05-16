<?php

namespace App\Domain\Anonymization;

use App\ServiceV2\Mailer\MailerService;
use Symfony\Component\Mime\Email;

readonly class AnonymizationMailer
{
    public function __construct(private MailerService $mailer, private array $adminMails)
    {
    }

    public function notifyAnonymizationSuccess(AnonymizationCount $anonymizationCount): void
    {
        $email = (new Email())
            ->subject("[CFN] Rapport d'anonymisation")
            ->text(sprintf(
                "La copie de la base de données de production sur la préproduction a réussi ainsi que l'anonymisation.\n\n
                - %d utilisateur.ice.s ont été copiés et anonymisés\n
                - %d documents ont été copiés et anonymisés\n
                - %d contacts ont été copiés et anonymisés\n
                - %d notes ont été copiés et anonymisés\n
                - %d événements ont été copiés et anonymisés\n",
                $anonymizationCount->getUsersCount(),
                $anonymizationCount->getDocumentsCount(),
                $anonymizationCount->getContactsCount(),
                $anonymizationCount->getNotesCount(),
                $anonymizationCount->getEventsCount(),
            ))
            ->to(...$this->adminMails);

        $this->mailer->send($email);
    }
}
