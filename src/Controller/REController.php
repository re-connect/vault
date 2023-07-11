<?php

namespace App\Controller;

use App\Api\Manager\ApiClientManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class REController extends AbstractController
{
    protected ?Request $request;

    public function __construct(
        RequestStack $requestStack,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
        protected ApiClientManager $apiClientManager,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getUser(): ?UserInterface
    {
        return parent::getUser();
    }

    protected function successFlashTranslate(string $message, string $domain = null): void
    {
        $message = $this->translator->trans($message, [], $domain);
        $this->addFlash('success', $message);
    }

    protected function successFlash(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function errorFlashTranslate(string $message, $domain = null): void
    {
        $message = $this->translator->trans($message, [], $domain);
        $this->addFlash('error', $message);
    }

    protected function errorFlash(string $message): void
    {
        $this->addFlash('error', $message);
    }
}
