<?php

namespace App\Command;

use App\Entity\Contact;
use App\Entity\CreatorUser;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-author-data',
    description: 'Migrate author information location on personal data (deposePar_Id -> Creator entity)',
)]
class MigrateAuthorDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = 10000;

        $classes = [
            Contact::class,
            Evenement::class,
            Note::class,
            Document::class,
            Dossier::class,
        ];

        foreach ($classes as $class) {
            $entities = $this->getEntitiesToUpdate($class);
            $io->info(sprintf('Migrating %s', $class));
            $i = 0;

            foreach ($entities as $entity) {
                ++$i;
                $creator = (new CreatorUser())->setEntity($entity->getDeposePar());
                $entity->addCreator($creator);
                if ($i > $batchSize) {
                    $this->em->flush();
                    $this->em->clear();
                    $i = 0;
                }
            }

            $this->em->flush();
            $io->success(sprintf('%d %s migrated', count($entities), $class));
        }

        return Command::SUCCESS;
    }

    private function getEntitiesToUpdate(string $className): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from($className, 'e')
            ->leftJoin('e.creators', 'c')
            ->andWhere(sprintf('c NOT INSTANCE OF %s', CreatorUser::class))
            ->andWhere('e.deposePar IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
