<?php

namespace App\Security;

use App\Entity\User;
use App\Event\UserEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Authenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(private RouterInterface $router, private EntityManagerInterface $em, private EventDispatcherInterface $eventDispatcher)
    {
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('re_main_login');
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $previousLogin = $user->getDerniereConnexionAt()?->format('Y-m-d');
        $now = new \DateTime();
        $user->setLastIp($request->getClientIp())->setDerniereConnexionAt($now);
        $this->em->persist($user);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user, $previousLogin !== $now->format('Y-m-d')));

        return new RedirectResponse($this->router->generate('re_user_loginEnd'));
    }
}
