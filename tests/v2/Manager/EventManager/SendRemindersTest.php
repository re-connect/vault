<?php

namespace App\Tests\v2\Manager\EventManager;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Attributes\Evenement;
use App\Entity\Rappel;
use App\ManagerV2\EventManager;
use App\Tests\Factory\BeneficiaireFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SendRemindersTest extends KernelTestCase
{
    use Factories;
    private EventManager $manager;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->manager = $this->getContainer()->get(EventManager::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @dataProvider provideTestSendSmsReminders
     */
    public function testSendSmsReminders(\DateTime $reminderDate, bool $isDue): void
    {
        // No reminders due
        $remindersRepo = $this->em->getRepository(Rappel::class);
        self::assertCount(0, $remindersRepo->getDueReminders());

        // Init test reminders
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        self::assertNotNull($beneficiary->getUser()->getTelephone());

        $event = (new Evenement())
            ->setBeneficiaire($beneficiary)
            ->setNom('test1')
            ->setDate(new \DateTime('tomorrow'))
            ->addRappel((new Rappel())
                ->setDate($reminderDate)
                ->setBEnvoye(false)
            );

        $this->em->persist($event);
        $this->em->flush();

        // Send due reminders
        $this->manager->sendReminders();

        foreach ($beneficiary->getEvenements() as $event) {
            foreach ($event->getRappels() as $reminder) {
                self::assertEquals($isDue, $reminder->getBEnvoye());
            }
        }
    }

    public function provideTestSendSmsReminders(): \Generator
    {
        yield 'Should not send reminder 13 hours in past' => [(new \DateTime('now'))->modify('-13 hours'), false];
        yield 'Should send reminder 12 hours in past' => [(new \DateTime('now'))->modify('-12 hours'), true];
        yield 'Should send reminder with date now()' => [new \DateTime('now'), true];
        yield 'Should send reminder in future' => [(new \DateTime('now'))->modify('+3 hours'), false];
    }
}
