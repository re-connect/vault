<?php

namespace App\Command;

use App\Repository\CreatorUserRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-creator-user',
    description: 'Remove orphan creator user',
)]
class FixCreatorUserCommand extends Command
{
    public function __construct(
        private readonly CreatorUserRepository $creatorUserRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = new ProgressBar($io);
        $io->info('Finding and removing entries and with entity_id referencing non-existent user');

        $i = 0;
        foreach ($progressBar->iterate($this->creatorUserRepository->findAll()) as $creatorUser) {
            if (!$this->userRepository->findOneBy(['id' => $creatorUser->getEntity()->getId()])) {
                $this->em->remove($creatorUser);
                ++$i;
            }
        }
        $this->em->flush();

        $io->success(sprintf('Removed %d creator user', $i));

        return Command::SUCCESS;
    }
}
