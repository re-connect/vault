<?php

namespace App\Command;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 're:import_beneficiaire',
    description: 'Importation de bénéficiaire par excel'
)]
class ImportBeneficiaireCommand extends Command
{
    private array $beneficaires;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'document',
                InputArgument::REQUIRED,
                'Quel est le nom du document ?'
            )
            ->addArgument(
                'centre',
                InputArgument::REQUIRED,
                'Quel est l\'id du centre ?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $centre = $this->em->find(Centre::class, $input->getArgument('centre'));

        $document = $input->getArgument('document');
        if (!is_file($documentPath = 'public/beneficiaire-import-files/in/'.$document)) {
            $output->writeln('Ce document n\'existe pas dans le dossier.');
            exit;
        }
        $row = 1;
        if (($handle = fopen($documentPath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $prenom = trim($data[0]);
                $nom = trim($data[1]);
                $dateNaissance = trim($data[2]);
                if ('' === $prenom) {
                    $output->writeln('Il manque le champ Prénom à la ligne '.$row.'.');
                    exit;
                }
                if ('' === $nom) {
                    $output->writeln('Il manque le champ Nom à la ligne '.$row.'.');
                    exit;
                }
                if ('' === $dateNaissance) {
                    $output->writeln('Il manque le champ Date de naissance à la ligne '.$row.'.');
                    exit;
                }
                [$jour, $mois, $annee] = explode('/', $dateNaissance);
                if (!checkdate($mois, $jour, $annee)) {
                    $output->writeln('Date de naissance au mauvais format (d/M/Y) à la ligne '.$row.'.');
                    exit;
                }
                $this->createBeneficiaire($prenom, $nom, $dateNaissance, $centre);
                ++$row;
            }
            fclose($handle);
        }
        $this->createCSV();

        return Command::SUCCESS;
    }

    private function createBeneficiaire($prenom, $nom, $dateNaissance, $centre, $telephone = null)
    {
        $user = new User();
        $password = 'stada';

        $user
            ->setPlainPassword($password)
            ->setPrenom($prenom)
            ->setNom($nom)
            ->setBActif(true)
            ->setTypeUser(User::USER_TYPE_BENEFICIAIRE)
            ->setTelephone($telephone);

        $beneficiaire = new Beneficiaire();
        $beneficiaire->setUser($user);

        $beneficiaire
            ->setDateNaissance(date_create_from_format('d/m/Y', $dateNaissance))
            ->setIsCreating(false)
            ->addCentre($centre);

        $beneficiaireCentre = $beneficiaire->getBeneficiairesCentres()->first();
        $beneficiaireCentre->setBValid(true);

        $this->em->persist($user);
        $this->em->persist($beneficiaire);
        $this->em->flush();

        $beneficiaire->getUser()->setPlainPassword($password);

        $this->beneficaires[] = $beneficiaire;
    }

    private function createCSV()
    {
        $date = (new \DateTime())->format('Ymd_His');
        $fileName = $date.'_beneficiaires.csv';
        $delimiteur = ';';
        $fichier_csv = fopen('public/beneficiaire-import-files/out/'.$fileName, 'w+');

        $data = [];
        foreach ($this->beneficaires as $beneficaire) {
            $data[] = [
                $beneficaire->getUser()->getPrenom(),
                $beneficaire->getUser()->getNom(),
                $beneficaire->getDateNaissance()->format('d/m/Y'),
                $beneficaire->getUser()->getTelephone(),
                $beneficaire->getUser()->getUsername(),
                $beneficaire->getUser()->getPlainPassword(),
            ];
        }

        foreach ($data as $row) {
            fputcsv($fichier_csv, $row, $delimiteur);
        }
        fclose($fichier_csv);
    }
}
