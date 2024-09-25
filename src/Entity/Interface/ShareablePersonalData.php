<?php

namespace App\Entity\Interface;

use App\Entity\Attributes\SharedPersonalData;

interface ShareablePersonalData
{
    public function getPublicDownloadUrl(): ?string;
    public function setPublicDownloadUrl(string $publicDownloadUrl): static;

    public static function createShareablePersonalData(): SharedPersonalData;
}
