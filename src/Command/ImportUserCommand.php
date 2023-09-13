<?php

namespace App\Command;

use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Repository\CentreRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\TabularDataReader;
use League\Csv\Writer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-users',
    description: 'Import users'
)]
class ImportUserCommand extends Command
{
    public function __construct(
        private readonly string $kernelProjectDir,
        private readonly EntityManagerInterface $em,
        private readonly CentreRepository $centreRepository,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('fileName', InputArgument::REQUIRED, 'Input file name');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = sprintf('%s/var/%s', $this->kernelProjectDir, $input->getArgument('fileName'));

        if (!$usersData = $this->getCsvRecords($filePath.'.csv', $io)) {
            return self::FAILURE;
        }

        $createdUsers = [];
        $progressBar = (new ProgressBar($output, $usersData->count()));
        foreach ($progressBar->iterate($usersData) as $userData) {
            $createdUsers[] = $this->createUser($userData);
        }

        $this->em->flush();
        $progressBar->finish();

        $this->createCSV($filePath, $createdUsers, $io);

        return Command::SUCCESS;
    }

    /** @param array<string, string|int|null> $userData */
    private function createUser(array $userData): User
    {
        [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'relayId' => $relayId,
            'email' => $email,
            'birthDate' => $birthDate,
            'phone' => $phone,
        ] = $userData + [
            'firstName' => null,
            'lastName' => null,
            'relayId' => null,
            'email' => null,
            'birthDate' => null,
            'phone' => null,
        ];

        $user = (new User())
            ->setPassword('temporaryPassword')
            ->setPrenom($firstName)
            ->setNom($lastName)
            ->setBActif(true)
            ->setTelephone($phone)
            ->setEmail($email);

        $this->em->persist($user);

        if ($birthDate) {
            $user->setBirthDate(date_create_from_format('d/m/Y', $birthDate));
            $this->createBeneficiary($user, $relayId);
        } else {
            $this->createPro($user, $relayId);
        }

        $this->em->flush();

        return $user;
    }

    /** @param User[] $users */
    private function createCSV(string $filePath, array $users, SymfonyStyle $io): void
    {
        $data = array_map(fn (User $user) => [
            $user->getPrenom(),
            $user->getNom(),
            $user->getUserIdentifier(),
            $user->getSubjectBeneficiaire()?->getDateNaissance()?->format('d/m/Y'),
            $user->getTelephone(),
            $user->getUsername(),
            $user->getEmail(),
            $user->getTypeUser(),
        ], $users);

        try {
            $csv = Writer::createFromFileObject(new \SplFileObject($filePath.'.out.csv', 'w'));
            $csv->insertAll($data);
        } catch (Exception $e) {
            $io->error(sprintf('Error reading CSV, cause : %s', $e->getMessage()));
        }
    }

    private function createBeneficiary(User $user, int $relayId = null): void
    {
        $user->setTypeUser(User::USER_TYPE_BENEFICIAIRE);
        $beneficiary = (new Beneficiaire())
            ->setUser($user)
            ->setDateNaissance($user->getBirthDate())
            ->setIsCreating(false);

        if ($relayId) {
            $relay = $this->centreRepository->find($relayId);
            if ($relay) {
                $beneficiary->addRelay($relay);
            }
        }

        $this->em->persist($beneficiary);
    }

    private function createPro(User $user, int $relayId = null): void
    {
        $user->setTypeUser(User::USER_TYPE_MEMBRE);
        $pro = (new Membre())->setUser($user);

        if ($relayId) {
            $relay = $this->centreRepository->find($relayId);
            if ($relay) {
                $userRelay = (new MembreCentre())->setCentre($relay)->setBValid(true);
                $pro->addMembresCentre($userRelay);
            }
        }

        $this->em->persist($pro);
    }

    /** @return TabularDataReader<mixed>|null */
    public function getCsvRecords(string $path, SymfonyStyle $io): ?TabularDataReader
    {
        try {
            $csv = Reader::createFromPath($path)->setHeaderOffset(0);

            return (new Statement())->limit(-1)->process($csv);
        } catch (\Exception $e) {
            $io->error(sprintf('Error reading CSV, cause : %s', $e->getMessage()));

            return null;
        }
    }
}
