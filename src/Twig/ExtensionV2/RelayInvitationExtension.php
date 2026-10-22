<?php

namespace App\Twig\ExtensionV2;

use App\Entity\Centre;
use App\Entity\User;
use App\Security\VoterV2\RelayInvitation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RelayInvitationExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('relayInvitation', static fn (User $user, Centre $relay): RelayInvitation => new RelayInvitation($user, $relay)),
        ];
    }
}
