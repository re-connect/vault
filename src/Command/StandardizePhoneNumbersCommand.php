<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:standardize-phone-numbers',
    description: 'Restore phone numbers as 12 digits with regional indicator',
)]
class StandardizePhoneNumbersCommand extends Command
{
    private UserRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, UserRepository $repository, string $name = null)
    {
        parent::__construct($name);
        $this->repository = $repository;
        $this->em = $em;
    }

    protected function configure()
    {
        $this->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete wrongly formatted numbers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $delete = $input->getOption('delete');
        $phonesToDelete = [];
        $users = $this->repository->createQueryBuilder('u')
            ->andWhere('LENGTH(u.telephone) <> 12')
            ->getQuery()
            ->getResult();

        /** @var User $user */
        foreach ($users as $user) {
            $phone = $user->getTelephone();
            $phone = str_replace(' ', '', $phone);
            $phone = str_replace('.', '', $phone);
            if (10 === \strlen($phone) && $this->startsWith($phone, '0')) {
                $phone = '+33'.ltrim($phone, '0');
            } elseif (11 === \strlen($phone) && $this->startsWith($phone, '+0')) {
                $phone = '+33'.ltrim($phone, '+0');
            } elseif (13 === \strlen($phone) && $this->startsWith($phone, '+330')) {
                $phone = '+33'.ltrim($phone, '+330');
            } elseif (11 === \strlen($phone) && $this->startsWith($phone, '33')) {
                $phone = '+33'.ltrim($phone, '33');
            } else {
                $phonesToDelete[] = $phone;
                if ($delete) {
                    $phone = null;
                }
            }
            $user->setTelephone($phone);
        }

        $this->em->flush();
        $io->error(\implode('|', $phonesToDelete));
        $io->success(sprintf('There are %s users with a phone number that has not 12 digits', count($users)));

        return Command::SUCCESS;
    }

    private function startsWith($haystack, $needle): bool
    {
        $length = \strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }
}
