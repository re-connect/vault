<?php

namespace App\Command;

use App\Entity\Attributes\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activate-admin-2fa',
    description: 'Add a short description for your command',
)]
class ActivateAdmin2faCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly PromptHelper $promptHelper)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        if (!$this->promptHelper->promptConfirm(
            $helper,
            $input,
            $output,
            'This command will activate 2fa of all admin and super admin users. Are you sure you want to continue ? (y/n)'
        )) {
            return Command::FAILURE;
        }

        $users = $this->em->getRepository(User::class)->findAllAdmins();
        $io->progressStart(count($users));

        $countActivated = 0;
        foreach ($users as $user) {
            /* @var User $user * */
            if (!$user->isMfaEnabled()) {
                $user->setMfaEnabled(true);
                ++$countActivated;
            } else {
                $io->warning(sprintf('User %s already activated', $user->getEmail()));
            }

            $io->progressAdvance();
        }

        $this->em->flush();

        $io->progressFinish();

        $io->success(sprintf('%d/%d admin users activated.', $countActivated, count($users)));

        return Command::SUCCESS;
    }
}
