<?php

namespace App\ControllerV2;

use App\Entity\Attributes\FolderIcon;
use App\Repository\FolderIconRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class FolderIconController extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/folder_icon/{name}', name: 'folder_icon_display')]
    public function show(string $name, FolderIconRepository $repository): BinaryFileResponse
    {
        return $this->file($repository->findOneBy(['name' => $name]));
    }

    #[Route(path: '/public/folder_icons', name: 'folder_icons')]
    public function list(FolderIconRepository $repository, TranslatorInterface $translator): JsonResponse
    {
        return $this->json(array_map(fn (FolderIcon $folderIcon) => [
            'id' => $folderIcon->getId(),
            'name' => $translator->trans($folderIcon->getName()),
            'url' => $folderIcon->getPublicFilePath(),
        ], $repository->findAll()));
    }
}
