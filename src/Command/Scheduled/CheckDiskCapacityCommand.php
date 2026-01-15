<?php

namespace App\Command\Scheduled;

use App\ServiceV2\Mailer\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:check-disk-capacity',
    description: 'Send email alert to tech team if server disk capacity percentage exceeds alert threshold',
)]
class CheckDiskCapacityCommand extends Command
{
    private const int CAPACITY_ALERT_THRESHOLD = 85;
    private const string CURRENT_FILE_SYSTEM_USAGE_COMMAND = 'df -h | grep $(findmnt -T $(pwd) -o SOURCE -n)';

    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly MailerService $mailer,
        private readonly string $env,
        private readonly array $adminMails,
        private readonly LoggerInterface $diskCapacityLogger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkAvailableCapacity();
        $this->logCapacityInfo();

        return Command::SUCCESS;
    }

    private function checkAvailableCapacity(): void
    {
        $process = Process::fromShellCommandline(sprintf("%s | awk '{print $5}'", self::CURRENT_FILE_SYSTEM_USAGE_COMMAND));
        $process->run();
        $capacityPercentage = $process->getOutput();

        if ($capacityPercentage && self::CAPACITY_ALERT_THRESHOLD <= intval($capacityPercentage)) {
            $this->mailer->send(
                (new Email())
                    ->subject('Alerte capacité disque dur serveur')
                    ->text(sprintf(
                        'La capacité actuelle du disque dur du serveur CFN (%s) est de %s',
                        $this->env,
                        $capacityPercentage,
                    ))
                    ->to(...$this->adminMails),
            );
        }
    }

    private function logCapacityInfo(): void
    {
        $process = Process::fromShellCommandline(sprintf("%s | awk '{print $2, $3, $4}'", self::CURRENT_FILE_SYSTEM_USAGE_COMMAND));
        $process->run();
        $this->diskCapacityLogger->info($process->getOutput());
    }
}
