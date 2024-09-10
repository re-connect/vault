<?php

namespace App\Controller\Api;

use App\ControllerV2\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final class MeController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/api/v3/users/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser());
    }
}
