<?php

namespace App\ServiceV2\Mailer;

use App\Entity\User;
use Symfony\Component\Mime\Email;

readonly class DuplicatedUsernameEmail
{
    /**
     * @param string[] $recipients
     */
    public static function create(string $sender, array $recipients, User $duplicatedUser): Email
    {
        return (new Email())
            ->from($sender)
            ->to(...$recipients)
            ->subject('[Coffre-Fort Numérique] Doublons bénéficiaire')
            ->text(sprintf('Doublons de username bénéficiaire : %s', $duplicatedUser->getUsername()));
    }
}
