<?php

namespace App\Entity\Interface;

use App\Entity\Attributes\SharedPersonalData;
use App\Entity\Beneficiaire;

interface ShareablePersonalData
{
    public function getPublicDownloadUrl(): ?string;

    public function setPublicDownloadUrl(string $publicDownloadUrl): static;

    public static function createShareablePersonalData(): SharedPersonalData;

    public function getBeneficiaire(): ?Beneficiaire;
}
