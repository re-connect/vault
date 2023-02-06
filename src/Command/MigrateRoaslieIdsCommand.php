<?php

namespace App\Command;

use App\ServiceV2\RosalieService;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-roaslie-ids',
    description: 'Migrate rosalie external ids from rosalie ID to SI-SIAO id',
)]
class MigrateRoaslieIdsCommand extends Command
{
    public function __construct(
        private readonly RosalieService $service,
        private readonly string $kernelProjectDir,
        string $name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $filePath = sprintf('%s/var/rosalie_ids.csv', $this->kernelProjectDir);
            $csv = Reader::createFromPath($filePath)->setDelimiter(',')->setHeaderOffset(0);
            $items = (new Statement())->limit(-1)->process($csv);

            $bar = new ProgressBar($output);
            foreach ($bar->iterate($items) as $item) {
                $this->service->migrateIdToSiSiaoId($item['id'], $item['number']);
            }
            $bar->finish();
        } catch (\Exception) {
            $io->error('Error reading or processing csv');
        }

        $io->success('Done');

        return Command::SUCCESS;
    }
}
