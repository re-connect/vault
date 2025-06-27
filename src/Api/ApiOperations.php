<?php

namespace App\Api;

use ApiPlatform\Metadata\Operation;

class ApiOperations
{
    public const TOGGLE_VISIBILITY = 'ToggleVisibility';

    public static function isSameOperation(Operation $operation, string $routeName): bool
    {
        return str_starts_with((string) $operation->getName(), $routeName);
    }
}
