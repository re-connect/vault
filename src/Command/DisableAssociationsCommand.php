<?php

namespace App\Command;

use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:disable-associations',
    description: 'Disable all association users',
)]
class DisableAssociationsCommand extends Command
{
    public function __construct(
        private readonly AssociationRepository $repository,
        private readonly EntityManagerInterface $em,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $associations = $this->repository->findAll();
        $io->info(sprintf('Found %d associations', count($associations)));

        foreach ($associations as $association) {
            $association->getUser()->disable(null);
        }

        $this->em->flush();
        $io->info('Disabled all associations');

        return Command::SUCCESS;
    }
}
