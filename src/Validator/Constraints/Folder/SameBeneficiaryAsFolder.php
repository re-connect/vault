<?php

namespace App\Validator\Constraints\Folder;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SameBeneficiaryAsFolder extends Constraint
{
    public string $message = 'folder_belongs_to_another_beneficiary';
}
