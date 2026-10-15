<?php

namespace App\EventSubscriber\Api;

use League\Bundle\OAuth2ServerBundle\Event\ScopeResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Partner scopes (ROLE_OAUTH2_*) are meant for client_credentials clients: user tokens are authorized by voters.
 * League grants any requested scope to a client stored without scopes, so they are dropped for user tokens here.
 */
#[AsEventListener(OAuth2Events::SCOPE_RESOLVE, 'onScopeResolve')]
readonly class Oauth2ScopeResolveSubscriber
{
    private const array USER_TOKEN_GRANTS = ['password', 'refresh_token'];
    private const string DEFAULT_SCOPE = 'default';

    public function onScopeResolve(ScopeResolveEvent $event): void
    {
        if (!in_array((string) $event->getGrant(), self::USER_TOKEN_GRANTS, true)) {
            return;
        }

        $event->setScopes(...array_values(array_filter(
            $event->getScopes(),
            fn (Scope $scope) => self::DEFAULT_SCOPE === (string) $scope,
        )));
    }
}
