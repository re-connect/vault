<?php

namespace App\Command\DataMigration;

use App\Entity\CreatorUser;
use App\Entity\User;
use App\Repository\BeneficiaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-cree-par-id',
    description: 'Migrate creator information on beneficiary (creePar_Id -> Creator entity)',
)]
class MigrateCreeParCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BeneficiaireRepository $beneficiaireRepository,
        $name = null,
    ) {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $benefs = $this->getBeneficiariesWithCreePar();
        $progressBar = new ProgressBar($output, count($benefs));
        $count = 0;

        foreach ($progressBar->iterate($benefs) as $benefId => $creatorId) {
            $benef = $this->beneficiaireRepository->find($benefId);
            if (!$benef->getCreatorUser()) {
                ++$count;
                $creator = (new CreatorUser())->setEntity($this->em->getRepository(User::class)->find($creatorId));
                $benef->addCreator($creator);
            }
        }

        $this->em->flush();
        $io->success(sprintf('Updated %d beneficiaries', $count));

        return Command::SUCCESS;
    }

    private function getBeneficiariesWithCreePar()
    {
        $conn = $this->em->getConnection();
        $results = $conn->executeQuery('SELECT id, creePar_id FROM beneficiaire b WHERE b.creePar_id is not null');

        return array_reduce($results->fetchAllAssociative(), function ($carry, $item) {
            $carry[$item['id']] = $item['creePar_id'];

            return $carry;
        }, []);
    }
}
