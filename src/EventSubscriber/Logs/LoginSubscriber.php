<?php

namespace App\EventSubscriber\Logs;

use App\ServiceV2\ActivityLogger;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security, private readonly ActivityLogger $logger, private readonly EntityManagerInterface $em)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
            TwoFactorAuthenticationEvents::COMPLETE => 'onAuthenticationCompleteEvent',
        ];
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $this->logger->logLogin();
    }

    public function onAuthenticationCompleteEvent(): void
    {
        $this->getUser()->resetMfaRetryCount();
        $this->em->flush();
    }
}
