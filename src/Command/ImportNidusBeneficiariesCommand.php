<?php

namespace App\Command;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\User;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-nidus-beneficiary',
    description: 'Import nidus beneficiary, with personnal documents',
)]
class ImportNidusBeneficiariesCommand extends Command
{
    public function __construct(
        private readonly string $kernelProjectDir,
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('folderName', InputArgument::REQUIRED, 'Input folder name');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createBeneficiary($this->getBeneficiaryRecord($input->getArgument('folderName')));

        return Command::SUCCESS;
    }

    private function getBeneficiaryRecord(string $folderName): array
    {
        $folderPath = sprintf('%s/var/nidus_import/%s', $this->kernelProjectDir, $folderName);
        $beneficiaryFilePath = sprintf('%s/user-profile.csv', $folderPath);
        $csv = Reader::createFromPath($beneficiaryFilePath, 'r')->setHeaderOffset(0);

        return (new Statement())->process($csv)->getIterator()->current();
    }

    private function createBeneficiary(array $record): void
    {
        $user = new User();
        $user
            ->setPlainPassword($this->userManager->getRandomPassword(100))
            ->setPrenom($record['name'])
            ->setNom($record['lastName'])
            ->setBActif(true)
            ->setTypeUser(User::USER_TYPE_BENEFICIAIRE)
            ->setTelephone($record['phone'] ?? null)
            ->setEmail($record['email'] ?? null);

        $beneficiaire = new Beneficiaire();
        $beneficiaire
            ->setUser($user)
            ->setDateNaissance(new \DateTime($record['birthdate']) ?? null);

        $this->em->persist($user);
        $this->em->flush();
    }
}
