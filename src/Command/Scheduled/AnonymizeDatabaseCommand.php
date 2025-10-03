<?php

namespace App\Command\Scheduled;

use App\ServiceV2\Mailer\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:anonymize-db',
    description: 'Anonymize database using db-tools bundle',
)]
class AnonymizeDatabaseCommand extends Command
{
    public function __construct(
        private readonly string $env,
        private readonly string $kernelProjectDir,
        private readonly array $adminMails,
        private readonly LoggerInterface $logger,
        private readonly MailerService $mailer,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    #[\Override]
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('noEmail', 'nomail', InputOption::VALUE_OPTIONAL, 'Prevent email from being sent', false);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('prod' === $this->env) {
            $output->writeln('!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
            $output->writeln('You are currently on the PRODUCTION environment');
            $output->writeln('!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
            $output->writeln('Do not even try to run this command on the production database');
            $output->writeln('!!!!!!!!!!!!!!!!!!!!!!!!!!!!');

            return self::FAILURE;
        }

        try {
            var_dump('Anonymizing database...');
            $this->executeAnonymization();
            var_dump('Anonymization ended');

            if (!$input->getOption('noEmail')) {
                var_dump('Sending email');
                $this->sendAnonymizationEmail();
                var_dump('Anonymization email sent');
            }
        } catch (\Exception $e) {
            $errorMessage = sprintf('Error anonymizing database. cause: %s', $e->getMessage());
            var_dump($errorMessage);
            $this->logger->error($errorMessage);
        }

        return self::SUCCESS;
    }

    private function executeAnonymization(): void
    {
        Process::fromShellCommandline(sprintf('php %s/bin/console db-tools:anonymize --local-database --no-restore --no-interaction', $this->kernelProjectDir))
            ->setTimeout(null)
            ->mustRun();
    }

    private function sendAnonymizationEmail(): void
    {
        $email = (new Email())
            ->subject("[CFN] Rapport d'anonymisation")
            ->text("La copie de la base de données de production sur la préproduction a réussi ainsi que l'anonymisation.")
            ->to(...$this->adminMails);

        $this->mailer->send($email);
    }
}
