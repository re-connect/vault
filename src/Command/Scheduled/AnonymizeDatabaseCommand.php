<?php

namespace App\Command\Scheduled;

use App\Domain\Anonymization\Anonymizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:anonymize-db',
    description: 'Anonymize documents',
)]
class AnonymizeDatabaseCommand extends Command
{
    public function __construct(
        private readonly Anonymizer $anonymizer,
        private readonly string $env,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('noEmail', 'nomail', InputOption::VALUE_OPTIONAL, 'Prevent email from being sent', false);
    }

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

        $this->anonymizer->anonymizeDatabase(!$input->getOption('noEmail'));

        return self::SUCCESS;
    }
}
