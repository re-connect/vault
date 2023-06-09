<?php

namespace App\HelperEntity;

class Notification
{
    /** @param NotificationAction[] $actions */
    public function __construct(
        public readonly string $title = '',
        public readonly string $subtitle = '',
        public readonly string $content = '',
        public readonly array $actions = []
    ) {
    }
}
