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
    name: 'app:force-admin-password-update',
    description: 'Invalidate password of all Reconnect admin users',
)]
class ForceAdminPasswordUpdateCommand extends Command
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
            'This command will invalidate the passwords of all admin users. Are you sure you want to continue ? (y/n)'
        )) {
            return Command::FAILURE;
        }

        $users = $this->em->getRepository(User::class)->findReconnectAdmins();

        foreach ($users as $user) {
            /* @var User $user * */
            $user->setHasPasswordWithLatestPolicy(false);
        }

        $this->em->flush();
        $io->success('Done');

        return Command::SUCCESS;
    }
}
