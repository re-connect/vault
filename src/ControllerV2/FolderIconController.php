<?php

namespace App\ControllerV2;

use App\Repository\FolderIconRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FolderIconController extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/folder_icon/{name}', name: 'folder_icon_display')]
    public function show(string $name, FolderIconRepository $repository): BinaryFileResponse
    {
        return new BinaryFileResponse($repository->findOneBy(['name' => $name]));
    }
}
