<?php

namespace App\ServiceV2\Traits;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait SessionsAwareTrait
{
    private RequestStack $requestStack;

    private function clearFlashMessage(): void
    {
        $session = $this->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->clear();
        }
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    private function addFlashMessage(string $type, string $message): void
    {
        $session = $this->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    private function clearAndAddFlashMessage(string $string, string $message): void
    {
        $this->clearFlashMessage();
        $this->addFlashMessage($string, $message);
    }
}
