<?php

namespace App\ServiceV2;

use App\Entity\Attributes\User;
use Psr\Log\LoggerInterface;

class ActivityLogger
{
    public function __construct(
        private readonly LoggerInterface $loginLogger,
    ) {
    }

    public function logLogin(User $user): void
    {
        $this->loginLogger->info('User logged in', [
            'id ' => $user->getId(),
            'date' => (new \DateTimeImmutable())->format('d/m/Y'),
            'time' => (new \DateTimeImmutable())->format('H:i'),
        ]);
    }
}
