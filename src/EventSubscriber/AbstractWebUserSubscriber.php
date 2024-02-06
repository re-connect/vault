<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Security\HelperV2\Oauth2Helper;
use App\ServiceV2\Traits\UserAwareTrait;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractWebUserSubscriber implements EventSubscriberInterface
{
    use UserAwareTrait;
    protected const FIRST_VISIT_ROUTES = ['re_main_change_lang', 'user_delete', 'user_first_visit', 'user_cgs'];

    public function __construct(
        private readonly Security $security,
        protected readonly RouterInterface $router,
        protected readonly TokenStorageInterface $tokenStorage,
        protected readonly Oauth2Helper $oauth2Helper,
    ) {
    }

    protected function isAuthenticatedWebUser(?User $user): bool
    {
        return $user instanceof User && !$this->isOauth2User();
    }

    private function isOauth2User(): bool
    {
        $token = $this->tokenStorage->getToken();

        return $token instanceof OAuth2Token && $this->oauth2Helper->getClient($token);
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
