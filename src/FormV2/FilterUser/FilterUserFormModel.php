<?php

namespace App\FormV2\FilterUser;

use App\Entity\Attributes\Centre;

class FilterUserFormModel
{
    public function __construct(public readonly ?string $search = '', public readonly ?Centre $relay = null)
    {
    }
}
