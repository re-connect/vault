<?php

namespace App\Security\VoterV2;

use App\Entity\Beneficiaire;
use App\Entity\Dossier;

final readonly class FolderDestination
{
    public function __construct(public Dossier $folder, public ?Beneficiaire $beneficiary)
    {
    }
}
