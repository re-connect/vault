<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Event;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Entity\SMS;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reminders are updated like the v2 API did (EvenementProvider::populate).
 */
class EventRemindersTest extends AbstractPasswordGrantApiTestCase
{
    public function testKeepsRemindersSentBackWithTheirIdAndCreatesNewOnes(): void
    {
        $event = $this->createEvent();
        $keptReminder = $this->addReminder($event, '+1 day');
        $removedReminder = $this->addReminder($event, '+2 days');
        $keptReminderId = $keptReminder->getId();
        $removedReminderId = $removedReminder->getId();
        $newReminderDate = new \DateTime('+3 days 10:00');

        // Clients send back the reminders they read, with their id and date
        $this->patch($event, ['rappels' => [
            ['id' => $keptReminderId, 'date' => $keptReminder->getDate()->format(\DateTimeInterface::ATOM)],
            ['date' => $newReminderDate->format(\DateTimeInterface::ATOM)],
        ]]);

        $this->assertResponseIsSuccessful();
        $reminders = $this->findReminders($event);
        $this->assertCount(2, $reminders);
        $this->assertNotNull($this->em->find(Rappel::class, $keptReminderId));
        $this->assertNull($this->em->find(Rappel::class, $removedReminderId));
        $this->assertContainsEquals($newReminderDate, array_map(fn (Rappel $reminder) => $reminder->getDate(), $reminders));
    }

    public function testArchivesSentRemindersAndDeletesOthersWhenRemoved(): void
    {
        $event = $this->createEvent();
        $sentReminderId = $this->addReminder($event, '-2 hours', true)->getId();
        $unsentReminderId = $this->addReminder($event, '+1 day')->getId();

        $this->patch($event, ['rappels' => []]);

        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $sentReminder = $this->em->find(Rappel::class, $sentReminderId);
        $this->assertNotNull($sentReminder);
        $this->assertTrue($sentReminder->getArchive());
        $this->assertNotNull($sentReminder->getSms());
        $this->assertNull($this->em->find(Rappel::class, $unsentReminderId));
    }

    public function testDoesNotRecreateSentReminderSentBackWithItsId(): void
    {
        $event = $this->createEvent();
        $sentReminder = $this->addReminder($event, '-2 hours', true);
        $sentReminderId = $sentReminder->getId();

        $this->patch($event, ['rappels' => [['id' => $sentReminderId, 'date' => $sentReminder->getDate()->format(\DateTimeInterface::ATOM)]]]);

        $this->assertResponseIsSuccessful();
        $reminders = $this->findReminders($event);
        $this->assertCount(1, $reminders);
        $this->assertSame($sentReminderId, $reminders[0]->getId());
        $this->assertTrue($reminders[0]->getBEnvoye());
        $this->assertFalse($reminders[0]->getArchive());
    }

    public function testPatchWithoutRemindersKeepsThem(): void
    {
        $event = $this->createEvent();
        $reminderId = $this->addReminder($event, '+1 day')->getId();

        $this->patch($event, ['nom' => 'Nouveau nom']);

        $this->assertResponseIsSuccessful();
        $reminders = $this->findReminders($event);
        $this->assertCount(1, $reminders);
        $this->assertSame($reminderId, $reminders[0]->getId());
    }

    private function createEvent(): Evenement
    {
        return EventFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
            'date' => new \DateTime('+5 days'),
        ]);
    }

    private function addReminder(Evenement $event, string $date, bool $isSent = false): Rappel
    {
        $reminder = (new Rappel())->setDate(new \DateTime($date))->setBEnvoye($isSent);
        $event->addRappel($reminder);
        $this->em->persist($reminder);
        if ($isSent) {
            $sms = SMS::createReminderSms($reminder, $event, $event->getBeneficiaire(), '0612345678');
            $reminder->setSms($sms);
            $this->em->persist($sms);
        }
        $this->em->flush();

        return $reminder;
    }

    /** @param array<string, mixed> $body */
    private function patch(Evenement $event, array $body): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/events/%d', $event->getId()), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
        ]);
    }

    /** @return Rappel[] */
    private function findReminders(Evenement $event): array
    {
        $this->em->clear();

        return $this->em->getRepository(Rappel::class)->findBy(['evenement' => $event->getId(), 'archive' => false]);
    }
}
