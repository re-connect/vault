<?php

namespace App\Command;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:anonymize-db',
    description: 'Anonymize documents',
)]
class AnonymizeDocumentsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private string $env;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $kernelEnvironment,
        string $name = null
    ) {
        $this->env = $kernelEnvironment;
        $this->entityManager = $entityManager;
        parent::__construct($name);
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

        $q = $this->entityManager->createQuery('update '.Document::class.' e set e.objectKey = ?1 , e.thumbnailKey = ?2 , e.nom = ?3 , e.extension = ?4')
            ->setParameter(1, 'anonymous.png')
            ->setParameter(2, 'anonymous-thumbnail.png')
            ->setParameter(3, 'Anonymous')
            ->setParameter(4, 'png');

        $q->execute();

        return self::SUCCESS;
    }
}
