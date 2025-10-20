<?php

namespace App\Command\Test;

use App\Repository\RappelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:log-sms-reminder-sending',
    description: 'Sends all due SMS reminders',
)]
class LogSmsReminderSending extends Command
{
    public function __construct(private readonly LoggerInterface $smsLogger, private readonly RappelRepository $reminderRepository, private readonly EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reminders = $this->reminderRepository->getDueReminders();
        foreach ($reminders as $reminder) {
            $now = new \DateTime('now');
            $reminderDate = $reminder->getDate()->setTimezone($now->getTimezone());
            $event = $reminder->getEvenement();

            if ($reminderDate < $now) {
                $reminder->setBEnvoye(true);
                $this->em->flush();
                $this->smsLogger->info(sprintf('Sms reminder sent with date : %s. Event : %s %s. User : %s',
                    $reminder->getDate()->format('d/m/Y à H\hi'),
                    $event->getNom(),
                    $event->getDate()->format('d/m/Y à H\hi'),
                    $event->getBeneficiaire()?->getUser()?->getId(),
                ));
            }
        }

        return Command::SUCCESS;
    }
}
