<?php

namespace App\Command\Test;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-local-api-clients',
    description: 'Create local API clients for testing purposes',
)]
class CreateLocalAPIClientsCommand extends Command
{
    private const string CREATE_LOCAL_OAUTH_CLIENTS = "insert into vault.oauth2_client (identifier, name, secret, grants, scopes, active) values  ('local_password_id', 'local_password', 'local_password_secret', 'password', 'default', 1), ('local_credentials_id', 'local_credentials', 'local_credentials_secret', 'client_credentials', 'default beneficiaries users centers', 1);";
    private const string CREATE_LOCAL_CLIENTS = "insert into vault.client (nom, secret, random_id, redirect_uris, allowed_grant_types, dossier_nom, dossier_image, actif, access, newClientIdentifier) values ('local_password', 'local_password_secret', 'local_password_id', '', '', 'local_password', null, 1, '', 'local_password_id'), ('local_credentials', 'local_credentials_secret', 'local_credentials_id', '', '', 'local_credentials', null, 1, '', 'local_credentials_id');";

    public function __construct(private readonly EntityManagerInterface $em, private readonly string $env, $name = null)
    {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('dev' !== $this->env) {
            $output->writeln('This command can only be run in the dev environment.');

            return Command::FAILURE;
        }
        $output->writeln('Creating local API clients...');
        try {
            $this->em->getConnection()->executeStatement(self::CREATE_LOCAL_OAUTH_CLIENTS);
            $this->em->getConnection()->executeStatement(self::CREATE_LOCAL_CLIENTS);
        } catch (Exception $e) {
            $output->writeln('Error creating local API clients: '.$e->getMessage());

            return Command::FAILURE;
        }
        $output->writeln('Done !');

        return Command::SUCCESS;
    }
}
