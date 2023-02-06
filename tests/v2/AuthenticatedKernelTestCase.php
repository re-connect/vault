<?php

namespace App\Tests\v2;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AuthenticatedKernelTestCase extends KernelTestCase
{
    protected function loginUser(string $email): void
    {
        $container = static::getContainer();
        /** @var \App\Entity\User $user */
        $user = $container->get(UserRepository::class)->findOneBy(['email' => $email]);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $container->get('security.token_storage')->setToken($token);

        $session = $container->get('session.factory')->createSession();
        $session->set('_security_main', serialize($token));
        $session->save();
    }
}
