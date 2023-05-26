<?php

namespace App\Entity\Interface;

interface FolderableEntityInterface
{
    public function hasParentFolder(): bool;
}
