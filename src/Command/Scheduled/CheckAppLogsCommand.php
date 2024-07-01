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
    name: 'app:check-app-logs',
    description: 'Send email alert to tech team if no logs have been logged during last week',
)]
class CheckAppLogsCommand extends Command
{
    private const array LOG_FILES = ['login', 'personal_data', 'user', 'relay', 'affiliation'];

    /**
     * @param string[] $adminMails
     */
    public function __construct(
        private readonly string $kernelProjectDir,
        private readonly array $adminMails,
        private readonly MailerService $mailer,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logDir = sprintf('%s/var/log/activity', $this->kernelProjectDir);

        $brokenLogs = $this->checkBrokenLogs($logDir);

        if (!empty($brokenLogs)) {
            $this->mailer->send(
                (new Email())
                    ->subject('CFN : Alerte fichier de log non fonctionnel')
                    ->text(sprintf(
                        'Aucune nouvelle entrée depuis la semaine dernière sur les fichiers de logs suivants : %s',
                        implode(', ', $brokenLogs),
                    ))
                    ->to(...$this->adminMails),
            );
        }

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function checkBrokenLogs(string $logDir): array
    {
        return array_filter(
            self::LOG_FILES,
            fn (string $logFile) => !$this->assertLogDuringLastWeek(sprintf('%s/%s.log', $logDir, $logFile))
        );
    }

    private function assertLogDuringLastWeek(string $filePath): bool
    {
        $process = Process::fromShellCommandline(sprintf("tail -1 %s | awk '{print $1}'", $filePath));
        $process->run();
        $logDate = new \DateTime(str_replace(['[', ']'], '', $process->getOutput()));

        return $logDate > (new \DateTime())->modify('-1 week');
    }
}
