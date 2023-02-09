<?php

namespace App\Command;

use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Repository\BeneficiaireRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-rosalie-ids-to-si-siao-ids',
    description: 'Migrate all external ids from Rosalie to SI-SIAO ids',
)]
class MigrateRosalieIdsToSiSiaoIdsCommand extends Command
{
    public function __construct(
        private readonly string $kernelProjectDir,
        private readonly BeneficiaireRepository $beneficiaireRepository,
        private readonly ClientRepository $clientRepository,
        private readonly EntityManagerInterface $em,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    public function getAssociativeRecords(array $records): array
    {
        return array_reduce(
            $records,
            function (array $carry, array $item) {
                $carry[$item['idRosalie']] = $item['idSiSiao'];

                return $carry;
            },
            []
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $csvPath = $this->kernelProjectDir.'/var/id-rosalie-to-sisiao.csv';
            $records = Reader::createFromPath($csvPath)->setHeaderOffset(0)->jsonSerialize();

            $this->updateBeneficiaries($this->getAssociativeRecords($records));

            $io->success('Done');

            return Command::SUCCESS;
        } catch (\Exception) {
            return Command::FAILURE;
        }
    }

    private function updateBeneficiaries(array $records): void
    {
        $rosalieClient = $this->clientRepository->find(2);
        $beneficiaries = $this->beneficiaireRepository->findByDistantIds(array_keys($records), $rosalieClient->getRandomId());

        foreach ($beneficiaries as $beneficiary) {
            $this->updateBeneficiary($beneficiary, $records, $rosalieClient);
        }

        $this->em->flush();
    }

    private function updateBeneficiary(Beneficiaire $beneficiary, array $records, Client $rosalieClient): void
    {
        if ($externalLink = $beneficiary->getExternalLinkForClient($rosalieClient)) {
            $siSiaoNumber = $records[$externalLink->getDistantId()] ?? null;
            $externalLink->setDistantId($siSiaoNumber);
            $beneficiary->setSiSiaoNumber($siSiaoNumber);
        }
    }
}
