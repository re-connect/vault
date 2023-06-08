<?php

namespace App\HelperEntity;

class Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
    ) {
    }
}
