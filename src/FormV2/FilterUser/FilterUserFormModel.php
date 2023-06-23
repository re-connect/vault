<?php

namespace App\FormV2\FilterUser;

use App\Entity\Centre;

class FilterUserFormModel
{
    public ?string $search = '';
    public ?Centre $relay = null;
}
