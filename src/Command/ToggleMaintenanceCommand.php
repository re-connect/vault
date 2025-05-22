<?php

namespace App\Command;

use App\Checker\FeatureFlagChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:toggle-maintenance',
    description: 'Toggle maintenance state',
)]
class ToggleMaintenanceCommand extends Command
{
    public const string FEATURE_FLAG_NAME = 'maintenance';

    public function __construct(private readonly FeatureFlagChecker $featureFlagChecker)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $maintenanceEnabled = $this->featureFlagChecker->isEnabled(self::FEATURE_FLAG_NAME);
        $message = $maintenanceEnabled
            ? 'This will make the application accessible again. Are you sure you want to enable it ? (y/n)'
            : '!!! Caution !!! This will make the application unavailable to all users. Are you sure you want to disable it ? (y/n)';

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        if ($this->promptConfirm($helper, $input, $output, $message)) {
            $maintenanceEnabled
                ? $this->featureFlagChecker->disable(self::FEATURE_FLAG_NAME)
                : $this->featureFlagChecker->enable(self::FEATURE_FLAG_NAME);

            $io->success(
                $this->featureFlagChecker->isEnabled(self::FEATURE_FLAG_NAME)
                    ? 'Application disabled'
                    : 'Application enabled',
            );

            return Command::SUCCESS;
        }

        $io->info(
            $maintenanceEnabled
                ? 'Aborted - Application still disabled'
                : 'Aborted - Application still enabled',
        );

        return Command::FAILURE;
    }

    public function promptConfirm(QuestionHelper $helper, InputInterface $input, OutputInterface $output, string $message): bool
    {
        $confirmQuestion = new ConfirmationQuestion($message, false);

        return $helper->ask($input, $output, $confirmQuestion);
    }
}
