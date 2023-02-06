<?php

namespace App\ManagerV2;

use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Repository\RappelRepository;
use App\ServiceV2\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EventManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificator,
        private readonly RappelRepository $reminderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function toggleVisibility(Evenement $event): void
    {
        $event->setBPrive(!$event->getBPrive());
        $this->em->flush();
    }

    public function updateReminders(Evenement $event): void
    {
        $reminders = $this->em->getRepository(Rappel::class)->findBy(['evenement' => $event->getId()]);

        foreach ($reminders as $reminder) {
            if (!$event->getRappels()->contains($reminder)) {
                $this->em->remove($reminder);
            }
            if ($reminder->getBEnvoye()) {
                $originalReminder = $this->em->getUnitOfWork()->getOriginalEntityData($reminder);
                $reminder->setDate($originalReminder['date']);
            }
        }
        $this->em->flush();
    }

    public function sendReminders(): void
    {
        try {
            $reminders = $this->reminderRepository->getDueReminders();

            foreach ($reminders as $reminder) {
                $utcTimezone = new \DateTimeZone('UTC');
                $nowUtc = new \DateTime('now', $utcTimezone);
                $reminderDateUtc = $reminder->getDate()->setTimezone($utcTimezone);

                if (!$reminder->getBEnvoye() && $reminderDateUtc < $nowUtc) {
                    $this->notificator->sendSmsReminder($reminder);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error sending sms reminders, cause %s', $e->getMessage()));
        }
    }
}
