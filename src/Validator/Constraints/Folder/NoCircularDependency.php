<?php

namespace App\Validator\Constraints\Folder;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoCircularDependency extends Constraint
{
}
