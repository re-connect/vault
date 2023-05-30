<?php

namespace App\Entity\Interface;

use App\Entity\Dossier;

interface FolderableEntityInterface
{
    public function hasParentFolder(): bool;

    public function move(?Dossier $parentFolder): void;
}
