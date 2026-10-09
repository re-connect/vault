<?php

namespace App\EventSubscriber\Api;

use App\Api\Manager\ApiClientManager;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs one line per API call, so that we can measure how much each route (and each API version) is still used
 * before removing the legacy ones. See the app:api:usage-report command.
 */
#[AsEventListener(event: KernelEvents::TERMINATE, method: 'onKernelTerminate')]
readonly class ApiRouteUsageSubscriber
{
    public const string MESSAGE = 'api_call';

    public function __construct(
        private LoggerInterface $apiUsageLogger,
        private ApiClientManager $apiClientManager,
        private Security $security,
    ) {
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $version = self::getVersion($request->getPathInfo());
        if (null === $version) {
            return;
        }

        $this->apiUsageLogger->info(self::MESSAGE, [
            'route' => $request->attributes->get('_canonical_route') ?? $request->attributes->get('_route'),
            'version' => $version,
            'method' => $request->getMethod(),
            'status' => $event->getResponse()->getStatusCode(),
            'client_id' => $this->apiClientManager->getCurrentTokenClientId(),
            'user_type' => $this->getUserType(),
        ]);
    }

    public static function getVersion(string $path): ?string
    {
        return match (true) {
            str_starts_with($path, '/api/v3') => 'v3',
            str_starts_with($path, '/api/v2') => 'v2',
            str_starts_with($path, '/oauth'), str_starts_with($path, '/api/token'), str_starts_with($path, '/api/authorize') => 'oauth',
            str_starts_with($path, '/api') => 'legacy',
            str_starts_with($path, '/appli') => 'appli',
            str_starts_with($path, '/public/folder_icons') => 'public',
            default => null,
        };
    }

    private function getUserType(): string
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user->getTypeUser() ?? 'user';
        }

        return null !== $this->apiClientManager->getCurrentTokenClientId() ? 'client' : 'anonymous';
    }
}
