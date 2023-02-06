<?php

namespace App\Command;

use App\ManagerV2\EventManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-reminders-sms',
    description: 'Sends all due SMS reminders',
)]
class SendRemindersSmsCommand extends Command
{
    public function __construct(private readonly EventManager $eventManager, $name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        date_default_timezone_set('UTC');
        try {
            $this->eventManager->sendReminders();

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
