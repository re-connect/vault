<?php

namespace App\EventSubscriber\Api;

use App\Domain\MFA\MfaCodeSender;
use App\Domain\PasswordStrength\WeakPasswordUpgrader;
use App\Domain\TermsOfUse\TermsOfUseHelper;
use App\ManagerV2\UserManager;
use App\Repository\UserRepository;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(RequestEvent::ACCESS_TOKEN_ISSUED, 'onAccessTokenIssued')]
readonly class Oauth2TokenIssuedSubscriber
{
    public function __construct(
        private UserRepository $repository,
        private UserManager $userManager,
        private MfaCodeSender $mfaCodeSender,
        private WeakPasswordUpgrader $weakPasswordUpgrader,
        private TermsOfUseHelper $termsOfUseHelper,
    ) {
    }

    public function onAccessTokenIssued(RequestAccessTokenEvent $event): void
    {
        $userId = (string) $event->getAccessToken()->getUserIdentifier();
        if (!$userId) {
            return;
        }

        $user = $this->repository->loadUserByIdentifier($userId);
        if (!$user) {
            return;
        }
        $this->userManager->handleUserLogin($user);

        parse_str((string) $event->getRequest()->getServerParams()['QUERY_STRING'], $queryParams);
        $this->termsOfUseHelper->checkAcceptTermsOfUse($user, $queryParams['_accept_terms_of_use'] ?? null);
        $this->weakPasswordUpgrader->checkUpdateWeakPassword($user, $queryParams['_new_password'] ?? null);
        $this->mfaCodeSender->checkCode($user, $queryParams['_auth_code'] ?? null);
    }
}
