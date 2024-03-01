<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\Centre;
use App\Entity\Region;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

readonly class DuplicatedUsernameEmail
{
    public static function create(string $defaultRecipient, User $duplicatedUser, User $authenticatedUser): Email
    {
        $pro = $authenticatedUser->getSubjectMembre();
        $centres = $pro->getCentres();
        $regions = $centres->map(fn (Centre $centre) => $centre->getRegion());
        $recipients = $regions->map(fn (?Region $region) => $region?->getEmail())->filter(fn (?string $recipient) => null !== $recipient);
        if ($recipients->isEmpty()) {
            $recipients->add($defaultRecipient);
        }

        return (new TemplatedEmail())
            ->to(...$recipients)
            ->subject('duplicate_mail_subject')
            ->htmlTemplate('v2/email/duplicated_user.html.twig')
            ->context(['duplicatedUser' => $duplicatedUser, 'userLang' => 'fr', 'client' => $duplicatedUser->getCreatorClient(), 'centres' => $centres, 'pro' => $pro]);
    }
}
