<?php

namespace App\Command;

use App\Entity\Centre;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-members-from-csv',
    description: 'Add a short description for your command',
)]
class CreateMembersFromCsvCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $csvPaths = [
            [
                843,
                'documents/import csv centre 843 - CSS Bondy - Feuille 1.csv',
                'cssbondy',
            ],
            [
                844,
                'documents/import csv centre 844 - CSSAPE - Feuille 1.csv',
                'cssape',
            ],
            [
                845,
                'documents/import csv centre 845 - CAMNA - Feuille 1.csv',
                'camna',
            ],
        ];

        foreach ($csvPaths as $csvPath) {
            $centre = $this->entityManager->getRepository(Centre::class)->find($csvPath[0]);

            if (!file_exists($csvPath[1])) {
                $io->error('file not found');

                return 1;
            }

            $csv = Reader::createFromPath($csvPath[1], 'r');

            $records = $csv->getRecords(['nom', 'prenom', 'email']);

            foreach ($records as $record) {
                $user = new User();
                $user->setNom($record['nom']);
                $user->setPrenom($record['prenom']);
                $user->setEmail($record['email']);
                $user->setPlainPassword($csvPath[2]);
                $user->setEnabled(true);
                $user->setUsername($record['nom'].'.'.$record['prenom']);

                $membre = new Membre();
                $membre->setUser($user);

                $membreCentre = new MembreCentre();
                $membreCentre->setBValid(true);
                $membreCentre->setDroits([MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true, MembreCentre::TYPEDROIT_GESTION_MEMBRES => false]);
                $membreCentre->setCentre($centre);

                $membre->addMembresCentre($membreCentre);

                $this->entityManager->persist($membre);
            }

            $this->entityManager->flush();
        }

        $io->success('Members create.');

        return 0;
    }
}
