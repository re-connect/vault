<?php

namespace App\ServiceV2;

use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    use UserAwareTrait;

    public function __construct(
        private readonly LoggerInterface $loginLogger,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function logLogin(): void
    {
        $user = $this->getUser();
        if ($user) {
            $user->setLastLogin(new \DateTime());
            $this->em->flush();
            $this->loginLogger->info('User logged in', [
                'id ' => $user->getId(),
                'date' => (new \DateTimeImmutable())->format('d/m/Y'),
                'time' => (new \DateTimeImmutable())->format('H:i'),
            ]);
        }
    }
}
