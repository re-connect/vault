<?php

namespace App\Command\Test;

use App\ManagerV2\EventManager;
use App\Repository\RappelRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:log-sms-reminder-sending',
    description: 'Sends all due SMS reminders',
)]
class LogSmsReminderSending extends Command
{
    public function __construct(private readonly LoggerInterface $smsLogger, private readonly RappelRepository $reminderRepository, $name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reminders = $this->reminderRepository->getDueReminders();
        foreach ($reminders as $reminder) {
            $now = new \DateTime('now');
            $event = $reminder->getEvenement();
            $eventDate = $event->getDate()->setTimezone($now->getTimezone());

            if ($eventDate < $now) {
                $this->smsLogger->log('info', sprintf('Sms reminder sent with date : %s. Event date : %s. User : %s',
                    $reminder->getDate()->format('d/m/Y à H\hi'),
                    $event->getDate()->format('d/m/Y à H\hi'),
                    $event->getBeneficiaire()?->getUser()?->getId(),
                ));
            }
        }

        return Command::SUCCESS;
    }
}
