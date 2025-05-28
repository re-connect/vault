<?php

namespace App\Controller\Api;

use App\ControllerV2\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class MeController extends AbstractController
{
    public function __invoke(): ?User
    {
        return $this->getUser();
    }
}
