<?php

namespace App\Entity\Interface;

use App\Entity\Attributes\Dossier;

interface FolderableEntityInterface
{
    public function hasParentFolder(): bool;

    public function move(?Dossier $parentFolder): void;

    public function canToggleVisibility(): bool;
}
