<?php

namespace App\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class PromptHelper
{
    public function promptConfirm(QuestionHelper $helper, InputInterface $input, OutputInterface $output, string $message): bool
    {
        $confirmQuestion = new ConfirmationQuestion($message, false);

        return $helper->ask($input, $output, $confirmQuestion);
    }
}
