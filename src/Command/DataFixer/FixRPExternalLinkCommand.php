<?php

namespace App\Command\DataFixer;

use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-rp-external-links',
    description: 'Fix broken rp external links',
)]
class FixRPExternalLinkCommand extends Command
{
    private int $removedLinks;
    private int $fixedLinks;

    public function __construct(
        private readonly BeneficiaireRepository $beneficiaireRepository,
        private readonly EntityManagerInterface $em,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        /** @var array<Beneficiaire> $beneficiaries */
        $beneficiaries = $this->beneficiaireRepository->findWithBrokenRPLink();
        if (!$this->promptConfirm(count($beneficiaries), $helper, $input, $output, $io)) {
            return Command::SUCCESS;
        }
        $this->removedLinks = 0;
        $this->fixedLinks = 0;
        try {
            foreach ($beneficiaries as $beneficiary) {
                match ($beneficiary->getCentres()->count()) {
                    0 => $this->deleteLink($beneficiary),
                    1 => $this->fixLink($beneficiary),
                    default => '',
                };
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        $io->info(sprintf('%s links removed', $this->removedLinks));
        $io->success(sprintf('%s links fixed', $this->fixedLinks));

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function deleteLink(Beneficiaire $beneficiary): void
    {
        $link = $beneficiary->getAxelExternalLink();
        if (!$link) {
            throw new \Exception(sprintf('Beneficiary %s should have an external link to RP, but none was found', $beneficiary->getId()));
        }
        $this->em->remove($link);
        $this->em->flush();
        ++$this->removedLinks;
    }

    /**
     * @throws \Exception
     */
    private function fixLink(Beneficiaire $beneficiary): void
    {
        $link = $beneficiary->getAxelExternalLink();
        if (!$link) {
            throw new \Exception(sprintf('Beneficiary %s should have an external link to RP, but none was found', $beneficiary->getId()));
        }
        $benefCentre = $beneficiary->getBeneficiairesCentres()->first();
        if (false === $benefCentre) {
            throw new \Exception(sprintf('Beneficiary %s should have a beneficiaireCentre, but none was found', $beneficiary->getId()));
        }
        $benefCentre->setBValid(false);
        $link->setBeneficiaireCentre($benefCentre);
        $this->em->flush();
        ++$this->fixedLinks;
    }

    public function promptConfirm(int $count, QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): bool
    {
        $io->warning(sprintf('Nombre de beneficiaires trouvés : %s', $count));
        $confirmQuestion = new ConfirmationQuestion('Voulez vous continuer ? (y/n) ', false);

        return $helper->ask($input, $output, $confirmQuestion);
    }
}
