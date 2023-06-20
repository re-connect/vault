<?php

namespace App\HelperEntity;

use Symfony\Component\Form\FormView;

class Notification
{
    /** @param NotificationAction[] $actions */
    public function __construct(
        public readonly string $title = '',
        public readonly string $subtitle = '',
        public readonly ?string $icon = null,
        public readonly string $content = '',
        public readonly array $actions = [],
        public readonly ?FormView $form = null,
    ) {
    }
}
