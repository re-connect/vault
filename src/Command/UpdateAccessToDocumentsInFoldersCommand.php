<?php

namespace App\Command;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 're:update-access-documents-folders',
    description: 'Update access to documents in folders.',
)]
class UpdateAccessToDocumentsInFoldersCommand extends Command
{
    // the name of the command (the part after "bin/console")
    private const BATCH_SIZE = 200;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('This command update access to documents in folders.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documents = $this->entityManager->createQuery('SELECT d FROM '.Document::class.' d WHERE d.dossier IS NOT NULL')->iterate();

        $i = 1;

        /* @var Document $document */
        foreach ($documents as $row) {
            $document = $row[0];

            $document->setBPrive($document->getDossier()->getBPrive());

            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush(); // Executes all updates.
                $this->entityManager->clear(); // Detaches all objects from Doctrine!
            }
            ++$i;
        }
        $this->entityManager->flush();

        return 0;
    }
}
