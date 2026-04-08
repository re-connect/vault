<?php

namespace App\Command\DataFixer;

use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-events-timezone',
    description: 'Fix events with timezone set to Abidjan from bug',
)]
class FixEventsTimezoneCommand extends Command
{
    public function __construct(private readonly EvenementRepository $eventRepo, private readonly EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = new ProgressBar($io);
        $io->info('Finding and removing future events having reminders and timezone set to Africa/Abidjan');

        $events = $this->eventRepo->createQueryBuilder('e')
            ->innerJoin('e.rappels', 'r')
            ->andWhere('r.date > :now')
            ->andWhere("e.timezone = 'Africa/Abidjan'")
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $i = 0;
        foreach ($progressBar->iterate($events) as $event) {
            $event->setTimezone('Europe/Paris');
            ++$i;
        }
        $this->em->flush();

        $io->success(sprintf('Updated %s events', $i));

        return Command::SUCCESS;
    }
}
