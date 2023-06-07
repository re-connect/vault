<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Repository\EvenementRepository;
use App\Repository\RappelRepository;
use App\ServiceV2\NotificationService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EventManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificator,
        private readonly RappelRepository $reminderRepository,
        private readonly EvenementRepository $eventRepository,
        private readonly LoggerInterface $logger,
        private Security $security
    ) {
    }

    /**
     * @return Evenement[]
     */
    public function getEvents(Beneficiaire $beneficiary, string $search = null): array
    {
        return $this->eventRepository->findFutureEventsByBeneficiary(
            $beneficiary,
            $this->isLoggedInUser($beneficiary->getUser()),
            $search
        );
    }

    public function toggleVisibility(Evenement $event): void
    {
        $event->toggleVisibility();
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
