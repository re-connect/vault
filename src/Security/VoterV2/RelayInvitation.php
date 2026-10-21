<?php

namespace App\Security\VoterV2;

use App\Entity\Centre;
use App\Entity\User;

final readonly class RelayInvitation
{
    public function __construct(public User $user, public Centre $relay)
    {
    }
}
