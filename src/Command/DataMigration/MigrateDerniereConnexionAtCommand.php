<?php

namespace App\Command\DataMigration;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-derniere-connexion-at',
    description: 'Migrate derniereConnexionAt to lastLogin',
)]
class MigrateDerniereConnexionAtCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserRepository $userRepository, ?string $name = null)
    {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();
        $progressBar = new ProgressBar($output, count($users));

        foreach ($progressBar->iterate($users) as $user) {
            $user->setLastLogin(max([$user->getDerniereConnexionAt(), $user->getLastLogin()]));
        }
        $this->em->flush();
        $io->success('Done !');

        return Command::SUCCESS;
    }
}
