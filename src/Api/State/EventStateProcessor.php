<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Repository\RappelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Updates the reminders of an event like the v2 API did (EvenementProvider::populate):
 * reminders sent back with their id (and date, which is required by validation) are kept as is, new reminders (without id) are created,
 * and removed reminders are archived if their SMS has already been sent, deleted otherwise.
 *
 * @implements ProcessorInterface<Evenement, Evenement|void>
 */
readonly class EventStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private RappelRepository $reminderRepository,
        private EntityManagerInterface $em,
    ) {
    }

    /** @param Evenement $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Evenement
    {
        $request = $context['request'] ?? null;
        $payload = $request instanceof Request ? $request->toArray() : [];
        if (array_key_exists('rappels', $payload)) {
            $this->updateReminders($data, is_array($payload['rappels']) ? $payload['rappels'] : []);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /** @param array<mixed> $payloadReminders */
    private function updateReminders(Evenement $event, array $payloadReminders): void
    {
        // Reminders denormalized from the payload can not hold an id: they are rebuilt below
        foreach ($event->getRappels() as $reminder) {
            if (null === $reminder->getId()) {
                $event->removeRappel($reminder);
            }
        }

        $keptIds = [];
        foreach ($payloadReminders as $payloadReminder) {
            if (is_array($payloadReminder) && !empty($payloadReminder['id'])) {
                $keptIds[] = (int) $payloadReminder['id'];
            }
        }

        $originalReminders = $this->reminderRepository->findBy(['evenement' => $event, 'archive' => false]);
        foreach ($originalReminders as $originalReminder) {
            if (in_array($originalReminder->getId(), $keptIds, true)) {
                $this->keepReminder($event, $originalReminder);
            } elseif (null !== $originalReminder->getSms()) {
                $originalReminder->setArchive(true);
                $this->keepReminder($event, $originalReminder);
            } else {
                $event->removeRappel($originalReminder);
                $this->em->remove($originalReminder);
            }
        }

        foreach ($payloadReminders as $payloadReminder) {
            if (is_array($payloadReminder) && empty($payloadReminder['id']) && !empty($payloadReminder['date'])) {
                $event->addRappel((new Rappel())->setDate(new \DateTime($payloadReminder['date'])));
            }
        }
    }

    private function keepReminder(Evenement $event, Rappel $reminder): void
    {
        if (!$event->getRappels()->contains($reminder)) {
            $event->addRappel($reminder);
        }
    }
}
