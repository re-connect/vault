<?php

namespace App\Tests\v2;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Test\Factories;

class AuthenticatedKernelTestCase extends KernelTestCase
{
    use Factories;

    protected function loginUser(string $email): void
    {
        $container = static::getContainer();
        /** @var \App\Entity\User $user */
        $user = $this->getTestUserFromDb($email);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $container->get('security.token_storage')->setToken($token);

        $session = $container->get('session.factory')->createSession();
        $session->set('_security_main', serialize($token));
        $session->save();
    }

    protected function getTestUserFromDb(string $email): User
    {
        return UserFactory::find(['email' => $email])->object();
    }

    protected function getPrivateMethod(string $className, string $method): \ReflectionMethod
    {
        $reflector = new \ReflectionClass($className);

        return $reflector->getMethod($method);
    }
}
