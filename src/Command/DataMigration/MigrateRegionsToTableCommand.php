<?php

namespace App\Command\DataMigration;

use App\Entity\Attributes\Centre;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-regions',
    description: 'Migrate regions from string to object, this command is meant to be run only once',
)]
class MigrateRegionsToTableCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
    }

    public const REGIONS_WITH_MAIL = [
        'Auvergne-Rhône-Alpes' => 'aura@reconnect.fr',
        'Bourgogne-Franche-Comté' => 'support@reconnect.fr',
        'Bretagne' => 'bretagne@reconnect.fr',
        'Centre-Val de Loire' => 'support@reconnect.fr',
        'Corse' => 'support@reconnect.fr',
        'Grand Est' => 'grand-est@reconnect.fr',
        'Hauts-de-France' => 'hauts-de-france@reconnect.fr',
        'Ile-de-France' => 'ile-de-france@reconnect.fr',
        'Normandie' => 'normandie@reconnect.fr',
        'Nouvelle-Aquitaine' => 'nouvelle-aquitaine@reconnect.fr',
        'Occitanie' => 'occitanie@reconnect.fr',
        'Pays de la Loire' => 'pays-de-la-loire@reconnect.fr',
        'Provence-Alpes-Côte d’Azur' => 'paca@reconnect.fr',
        'Autre' => 'support@reconnect.fr',
    ];

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->promptConfirm($input, $output, $io)) {
            return Command::SUCCESS;
        }
        $this->createRegions();
        $this->migrateRegions($output);

        $io->success('Done !');

        return Command::SUCCESS;
    }

    private function createRegions(): void
    {
        foreach (self::REGIONS_WITH_MAIL as $regionName => $email) {
            $region = (new Region())->setName($regionName)->setEmail($email);
            $this->em->persist($region);
        }
        $this->em->flush();
    }

    private function migrateRegions(OutputInterface $output): void
    {
        $regionRepo = $this->em->getRepository(Region::class);
        $centresRepo = $this->em->getRepository(Centre::class);
        $centres = $centresRepo->findAllWithRegionAsString();
        $progressBar = new ProgressBar($output, count($centres));

        foreach ($progressBar->iterate($centres) as $centre) {
            $centre->setRegion($regionRepo->findOneBy(['name' => $centre->getRegionAsString()]));
        }
        $this->em->flush();
    }

    private function promptConfirm(InputInterface $input, OutputInterface $output, SymfonyStyle $io): bool
    {
        $helper = $this->getHelper('question');
        $io->warning('Cette commmande va créer toutes les entités Régions et les associer aux Centres.');
        $confirmQuestion = new ConfirmationQuestion('Voulez vous continuer ? (y/n) ', false);

        return $helper->ask($input, $output, $confirmQuestion);
    }
}
