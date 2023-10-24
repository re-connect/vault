<?php

namespace App\Command;

use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-folders-circular',
    description: 'Fix folders circular dependancy',
)]
class FixFoldersCircularDependancyCommand extends Command
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
        $progressBar = new ProgressBar($io);
        $io->info('Finding and removing circular dependancies');

        $i = 0;
        foreach ($progressBar->iterate($this->folderRepository->findWithParentFolder()) as $folder) {
            $parent = $folder->getDossierParent();
            if (null !== $parent && $folder === $parent->getDossierParent()) {
                $folder->setDossierParent();
                $parent->setDossierParent();
                ++$i;
            }
        }
        $this->em->flush();

        $io->success(sprintf('Removed %d circular dependancies', $i));

        return Command::SUCCESS;
    }
}
