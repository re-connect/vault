<?php

namespace App\Command\DataChecker;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Repository\BeneficiaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-vaults-storage',
    description: 'Checks the storage for vaults summing them to check inconsistencies',
)]
class CheckVaultsStorageCommand extends Command
{
    public const BATCH_SIZE = 1000;
    private int $inconsistentBeneficiariesCount = 0;

    public function __construct(
        private readonly BeneficiaireRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('firstIndex', InputArgument::OPTIONAL, 'Offsets the query to batch process', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $firstIndex = $input->getArgument('firstIndex') ?? 0;

        $io->note(sprintf('Fetching vaults, starting at index : %s', $firstIndex));

        $beneficiariesCount = $this->repository->count([]);
        $io->info(sprintf('Found a total of %s beneficiaries', $beneficiariesCount));

        $beneficiariesBatch = $this->repository->findByPaginated($firstIndex, self::BATCH_SIZE);
        $lastIndex = $firstIndex + count($beneficiariesBatch) - 1;

        $io->success(sprintf('Iterating over beneficiaries from index %s to %s', $firstIndex, $lastIndex));
        $progressBar = new ProgressBar($output, count($beneficiariesBatch));
        foreach ($progressBar->iterate($beneficiariesBatch) as $beneficiary) {
            $this->checkVaultSize($beneficiary);
        }

        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('Done, %d inconsistent vaults found', $this->inconsistentBeneficiariesCount));
        $io->success('Flushing new storage values');
        $this->em->flush();
        $io->success(sprintf('Done, last index used was %s', $lastIndex));

        return Command::SUCCESS;
    }

    private function checkVaultSize(Beneficiaire $beneficiary): void
    {
        $cumulatedSize = $beneficiary->getDocuments()
            ->reduce(fn (int $acc, Document $document) => $acc + $document->getTaille(), 0);
        if ($cumulatedSize !== $beneficiary->getTotalFileSize()) {
            $beneficiary->setTotalFileSize($cumulatedSize);
            ++$this->inconsistentBeneficiariesCount;
        }
    }
}
