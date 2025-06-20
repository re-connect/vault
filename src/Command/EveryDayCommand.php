<?php

namespace App\Command;

use App\Manager\SMSManager;
use App\Manager\StatistiqueCentreManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 're:everyDay',
)]
class EveryDayCommand extends Command
{
    public function __construct(
        private readonly StatistiqueCentreManager $statistiqueCentreManager,
        private readonly SMSManager $SMSManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //      Mettre à null le champ 'SmsPasswordResetCode' pour les utilisateurs n'ayant pas réinitialisé leur mot de passe depuis plus de 24h.
        $this->SMSManager->nullifySmsPasswordResetCodesFromYesterday();

        $this->statistiqueCentreManager->storeStatistics();

        //      Informations pour le log
        $date = new \DateTime();
        $now = $date->format('Y-m-d H:i:s').PHP_EOL;
        $log = ' ---------- '.PHP_EOL.'Commande effectué à : '.$now.PHP_EOL;
        echo $log;

        return 0;
    }
}
