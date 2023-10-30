<?php

namespace App\Command;

use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-folders-circular',
    description: 'Fix folders circular dependency',
)]
class FixFoldersCircularDependencyCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DossierRepository $folderRepository,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Finding and removing circular dependencies');

        $folders = $this->folderRepository->findWithCircularDependency();
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        if (!$this->promptConfirm(count($folders), $helper, $input, $output, $io)) {
            $io->info('No changes performed');

            return Command::SUCCESS;
        }

        $count = $this->fixCircularDependencies($folders, $io);
        $io->success(sprintf('Removed %d circular dependencies.', $count));

        return Command::SUCCESS;
    }

    private function fixCircularDependencies(array $folders, SymfonyStyle $io): int
    {
        $count = 0;
        $progressBar = new ProgressBar($io);
        foreach ($progressBar->iterate($folders) as $folder) {
            $parent = $folder->getDossierParent();
            if (null !== $parent && $folder === $parent->getDossierParent()) {
                $folder->setDossierParent();
                $parent->setDossierParent();
                ++$count;
            }
        }
        $this->em->flush();

        return $count;
    }

    public function promptConfirm(int $count, QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): bool
    {
        $io->warning(sprintf('Found %s folders with circular dependencies.', $count));
        $confirmQuestion = new ConfirmationQuestion('Are you sure ? (y/n) ', false);

        return $helper->ask($input, $output, $confirmQuestion);
    }
}
