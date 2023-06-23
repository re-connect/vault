<?php

namespace App\HelperEntity;

class NotificationAction
{
    public function __construct(
        public readonly string $label,
        public readonly string $path,
        public readonly ?string $color = null,
    ) {
    }
}
