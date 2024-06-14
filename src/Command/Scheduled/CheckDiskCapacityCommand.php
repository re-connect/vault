<?php

namespace App\Command\Scheduled;

use App\ServiceV2\Mailer\MailerService;
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

    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly MailerService $mailer,
        private readonly string $env,
        private readonly array $adminMails,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = Process::fromShellCommandline("df -h | grep $(findmnt -T $(pwd) -o SOURCE -n) | awk '{print $5}'");
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

        return Command::SUCCESS;
    }
}
