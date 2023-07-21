<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:enable-api-client',
    description: 'Enables an api client with client_credentials grant',
)]
class EnableApiClientCommand extends Command
{
    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        private readonly EntityManagerInterface $em,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('clientName', InputArgument::REQUIRED, 'Client to enable');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clientName = $input->getArgument('clientName');

        /** @var AbstractClient $client */
        foreach ($this->clientManager->list(null) as $client) {
            if ($client->getName() === $clientName) {
                $client->setGrants(new Grant('client_credentials'));
                $this->em->flush();
            }
        }

        $io->success(sprintf('Client %s successfully enabled with client_credentials grant', $clientName));

        return Command::SUCCESS;
    }
}
