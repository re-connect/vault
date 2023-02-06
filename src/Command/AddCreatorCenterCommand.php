<?php

namespace App\Command;

use App\Entity\CreatorCentre;
use App\Repository\BeneficiaireRepository;
use App\Repository\CentreRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-creator-center',
    description: 'Add creator center on beneficiary from csv file',
)]
class AddCreatorCenterCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BeneficiaireRepository $beneficiaryRepository,
        private readonly CentreRepository $centreRepository,
        private readonly string $kernelProjectDir,
        $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('fileName', InputArgument::REQUIRED, 'File name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fileName = $input->getArgument('fileName');
        $filePath = sprintf('%s/var/%s.csv', $this->kernelProjectDir, $fileName);
        $count = 0;

        try {
            $csv = Reader::createFromPath($filePath);
            $csv->setDelimiter(',');
            $csv->setHeaderOffset(0);
            $stmt = (new Statement())->limit(-1);
            $records = $stmt->process($csv);

            foreach ($records as $record) {
                [
                    'beneficiaryId' => $beneficiaryId,
                    'centerId' => $centerId
                ] = $record;

                $io->note(sprintf('Finding beneficiary with Id: %s and center with Id: %s', $beneficiaryId, $centerId));
                $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);
                $centre = $this->centreRepository->find($centerId);

                if (!$beneficiary || !$centre) {
                    $io->error('Did not find entities');

                    return self::FAILURE;
                }

                $user = $beneficiary->getUser();
                if ($creatorCentre = $user->getCreatorCentre()) {
                    $io->error(sprintf('User %s already has creator center : %s', $user->getFullName(), $creatorCentre->getEntity()->getNom()));

                    return self::FAILURE;
                }

                $io->success(sprintf('Beneficiary %s found for id %d, add creator center : %s', $user->getFullName(), $beneficiaryId, $centre->getNom()));
                $user->addCreator((new CreatorCentre())->setEntity($centre));
                ++$count;
            }
        } catch (Exception $e) {
            $io->error(sprintf('There has been an error during csv processing: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $this->em->flush();
        $io->success(sprintf('%d beneficiaries updated', $count));

        return Command::SUCCESS;
    }
}
